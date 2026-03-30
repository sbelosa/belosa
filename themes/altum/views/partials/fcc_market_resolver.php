<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_market_resolver_allowed_markets = array_values(array_unique(array_filter(array_map([\Altum\Link::class, 'resolve_forever_market_country_code'], $data->allowed_markets ?? []))));
$fcc_market_resolver_cookie_name = \Altum\Link::get_forever_market_cookie_name();
?>

<?php if($fcc_market_resolver_allowed_markets): ?>
    <?php ob_start() ?>
    <script>
    (() => {
        const allowedMarkets = <?= json_encode($fcc_market_resolver_allowed_markets) ?>;
        const cookieName = <?= json_encode($fcc_market_resolver_cookie_name) ?>;
        const sessionKey = 'fcc-market-resolver-last-market';

        const getCookie = name => {
            const cookie = document.cookie
                .split('; ')
                .find(row => row.startsWith(`${name}=`));

            return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : null;
        };

        const setCookie = (name, value) => {
            document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=${60 * 60 * 24 * 365}; SameSite=Lax`;
        };

        const timezone = Intl.DateTimeFormat?.().resolvedOptions?.().timeZone || '';
        const timezoneMap = {
            'Europe/Zagreb': 'hr',
            'Europe/Sarajevo': 'ba',
            'Europe/Belgrade': 'rs',
            'Europe/Ljubljana': 'si',
            'Europe/Tirane': 'al',
            'Europe/Pristina': 'al',
            'Asia/Dubai': 'ae',
            'Europe/Berlin': 'de',
        };

        const regionMap = {
            'hr': 'hr',
            'ba': 'ba',
            'rs': 'rs',
            'si': 'si',
            'al': 'al',
            'xk': 'al',
            'ae': 'ae',
            'de': 'de',
        };

        const languageMap = {
            'hr': 'hr',
            'bs': 'ba',
            'sr': 'rs',
            'sl': 'si',
            'sq': 'al',
            'de': 'de',
            'ar': 'ae',
        };

        const localeCandidates = [];

        if(Array.isArray(navigator.languages)) {
            localeCandidates.push(...navigator.languages);
        }

        if(navigator.language) {
            localeCandidates.push(navigator.language);
        }

        let market = null;

        if(timezoneMap[timezone] && allowedMarkets.includes(timezoneMap[timezone])) {
            market = timezoneMap[timezone];
        }

        if(!market) {
            for(const locale of localeCandidates) {
                if(!locale || typeof locale !== 'string') {
                    continue;
                }

                const parts = locale.toLowerCase().split('-');
                const languageCode = parts[0] || null;
                const regionCode = parts[1] || null;

                if(regionCode && regionMap[regionCode] && allowedMarkets.includes(regionMap[regionCode])) {
                    market = regionMap[regionCode];
                    break;
                }

                if(languageCode && languageMap[languageCode] && allowedMarkets.includes(languageMap[languageCode])) {
                    market = languageMap[languageCode];
                    break;
                }
            }
        }

        if(!market) {
            return;
        }

        const currentCookieMarket = getCookie(cookieName);
        const lastAttemptedMarket = window.sessionStorage.getItem(sessionKey);

        if(currentCookieMarket === market || lastAttemptedMarket === market) {
            return;
        }

        setCookie(cookieName, market);
        window.sessionStorage.setItem(sessionKey, market);
        window.location.reload();
    })();
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript', 'fcc_market_resolver_' . md5(json_encode($fcc_market_resolver_allowed_markets))); ?>
<?php endif ?>
