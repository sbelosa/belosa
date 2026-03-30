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
        const geoSessionKey = 'fcc-market-resolver-geo-attempted';

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

        const normalizeMarket = value => {
            if(!value || typeof value !== 'string') {
                return null;
            }

            const normalizedValue = value.trim().toLowerCase().slice(0, 2);

            if(normalizedValue === 'xk') {
                return 'al';
            }

            return allowedMarkets.includes(normalizedValue) ? normalizedValue : null;
        };

        const applyMarket = market => {
            if(!market) {
                return false;
            }

            const currentCookieMarket = getCookie(cookieName);
            const lastAttemptedMarket = window.sessionStorage.getItem(sessionKey);

            if(currentCookieMarket === market || lastAttemptedMarket === market) {
                return false;
            }

            setCookie(cookieName, market);
            window.sessionStorage.setItem(sessionKey, market);
            window.location.reload();

            return true;
        };

        const resolveMarketFromBrowserSignals = () => {
            if(timezoneMap[timezone] && allowedMarkets.includes(timezoneMap[timezone])) {
                return timezoneMap[timezone];
            }

            return null;
        };

        const currentCookieMarket = getCookie(cookieName);
        const browserFallbackMarket = resolveMarketFromBrowserSignals();

        if(currentCookieMarket && !allowedMarkets.includes(currentCookieMarket)) {
            setCookie(cookieName, '');
        }

        if(currentCookieMarket && browserFallbackMarket && currentCookieMarket !== browserFallbackMarket) {
            window.sessionStorage.removeItem(sessionKey);
        }

        const geoAttempted = window.sessionStorage.getItem(geoSessionKey) === '1';

        const applyBrowserFallback = () => {
            if(browserFallbackMarket) {
                return applyMarket(browserFallbackMarket);
            }

            if(currentCookieMarket) {
                setCookie(cookieName, '');
                window.sessionStorage.removeItem(sessionKey);
            }

            return false;
        };

        const tryGeoResolvers = async () => {
            const geoRequests = [
                {
                    url: 'https://get.geojs.io/v1/ip/country.json',
                    extractCountryCode: payload => payload?.country
                },
                {
                    url: 'https://ipwho.is/',
                    extractCountryCode: payload => payload?.country_code
                },
            ];

            for(const geoRequest of geoRequests) {
                try {
                    const controller = new AbortController();
                    const timeout = window.setTimeout(() => controller.abort(), 2500);

                    const response = await fetch(geoRequest.url, {
                        method: 'GET',
                        cache: 'no-store',
                        mode: 'cors',
                        signal: controller.signal,
                    });

                    window.clearTimeout(timeout);

                    if(!response.ok) {
                        continue;
                    }

                    const payload = await response.json();
                    const market = normalizeMarket(geoRequest.extractCountryCode(payload));

                    if(market && applyMarket(market)) {
                        return true;
                    }
                } catch(error) {
                    /* Ignore geo resolver failures and fall back to browser hints. */
                }
            }

            return false;
        };

        if(!geoAttempted) {
            window.sessionStorage.setItem(geoSessionKey, '1');

            tryGeoResolvers().then(didApplyGeoMarket => {
                if(!didApplyGeoMarket) {
                    applyBrowserFallback();
                }
            });

            return;
        }

        applyBrowserFallback();
    })();
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript', 'fcc_market_resolver_' . md5(json_encode($fcc_market_resolver_allowed_markets))); ?>
<?php endif ?>
