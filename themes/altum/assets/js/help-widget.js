/* Custom code: FC-2026-02-24: global help widget logic */
(function () {
    'use strict';

    var injectedConfig = window.FCC_HELP_CONFIG;
    if (!Array.isArray(injectedConfig) && injectedConfig && Array.isArray(injectedConfig.help_widget_items)) {
        injectedConfig = injectedConfig.help_widget_items;
    }
    var HELP_CONFIG = Array.isArray(injectedConfig) ? injectedConfig : [];

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function normalizePathname(pathname) {
        var match = pathname.match(/^\/([a-z]{2})\//i);
        if (match) {
            return pathname.replace(/^\/[a-z]{2}(\/|$)/i, '/');
        }
        return pathname;
    }

    function getPageKey() {
        var pathname = normalizePathname(window.location.pathname || '');
        var search = window.location.search || '';
        return pathname + search;
    }

    function findConfig() {
        var pathname = normalizePathname(window.location.pathname || '');
        var search = window.location.search || '';
        for (var i = 0; i < HELP_CONFIG.length; i += 1) {
            var entry = HELP_CONFIG[i];
            if (entry.enabled === 0 || entry.enabled === false) {
                continue;
            }
            if (entry.match.pathname && entry.match.pathname !== pathname) {
                continue;
            }
            if (entry.match.pathnamePrefix && pathname.indexOf(entry.match.pathnamePrefix) !== 0) {
                continue;
            }
            if (entry.match.searchIncludes && search.indexOf(entry.match.searchIncludes) === -1) {
                continue;
            }
            return entry;
        }
        return null;
    }

    function createHelpWidget(config) {
        var pageKey = getPageKey();
        var storageKey = 'fcc_help_seen::' + pageKey;

        var overlay = document.createElement('div');
        overlay.className = 'fcc-help-overlay';

        var panel = document.createElement('aside');
        panel.className = 'fcc-help-panel';

        var header = document.createElement('div');
        header.className = 'fcc-help-panel-header';

        var title = document.createElement('h3');
        title.className = 'fcc-help-panel-title';
        title.textContent = config.title;

        header.appendChild(title);

        var body = document.createElement('div');
        body.className = 'fcc-help-panel-body';

        if (config.vimeoId) {
            var iframe = document.createElement('iframe');
            iframe.className = 'fcc-help-iframe';
            iframe.setAttribute('allow', 'fullscreen; picture-in-picture');
            iframe.setAttribute('allowfullscreen', 'true');
            iframe.src = 'https://player.vimeo.com/video/' + config.vimeoId + '?autoplay=0';
            body.appendChild(iframe);
        }

        if (config.description) {
            var videoDescription = document.createElement('p');
            videoDescription.className = 'fcc-help-panel-description';
            videoDescription.textContent = config.description;
            body.appendChild(videoDescription);
        }

        if (config.extraHtml) {
            var extraBlock = document.createElement('div');
            extraBlock.className = 'fcc-help-extra-html';
            extraBlock.innerHTML = config.extraHtml;
            body.appendChild(extraBlock);
        }

        if (Array.isArray(config.bullets) && config.bullets.length) {
            var bullets = document.createElement('ul');
            bullets.className = 'fcc-help-bullets';
            for (var i = 0; i < config.bullets.length; i += 1) {
                var li = document.createElement('li');
                li.textContent = config.bullets[i];
                bullets.appendChild(li);
            }
            body.appendChild(bullets);
        }

        panel.appendChild(header);
        panel.appendChild(body);

        var button = document.createElement('button');
        button.className = 'fcc-help-button fcc-assist-button';
        button.type = 'button';
        button.setAttribute('aria-label', 'Video edukacija');
        button.setAttribute('aria-expanded', 'false');
        button.innerHTML = '<i class="fas fa-graduation-cap" aria-hidden="true"></i><span class="fcc-assist-label">Video edukacija</span>';

        var aiToggle = document.getElementById('fcc-zapier-toggle');
        var dock = document.createElement('div');
        dock.className = 'fcc-assist-dock';

        if (aiToggle) {
            aiToggle.classList.add('fcc-assist-button');
            aiToggle.textContent = 'Ai Savjetnik';
            document.body.classList.add('fcc-assist-has-ai');
        }

        var tooltip = document.createElement('div');
        tooltip.className = 'fcc-help-tooltip';
        tooltip.textContent = window.FCC_HELP_TOOLTIP || 'Novi si ovdje? Pogledaj kratku video uputu.';

        var showTooltip = !localStorage.getItem(storageKey);

        function isTutorialActive() {
            return document.body.classList.contains('fcc-tour-mode');
        }

        function closeAiShell() {
            var aiShell = document.getElementById('fcc-zapier-shell');

            if (aiShell) {
                aiShell.classList.remove('is-open');
                aiShell.setAttribute('aria-hidden', 'true');
            }

            if (aiToggle) {
                aiToggle.setAttribute('aria-expanded', 'false');
                aiToggle.textContent = 'Ai Savjetnik';
            }
        }

        function closePanel() {
            overlay.classList.remove('is-open');
            panel.classList.remove('is-open');
            document.body.classList.remove('fcc-help-open');
            button.setAttribute('aria-expanded', 'false');
            button.innerHTML = '<i class="fas fa-graduation-cap" aria-hidden="true"></i><span class="fcc-assist-label">Video edukacija</span>';
        }

        function openPanel() {
            if (isTutorialActive()) {
                return;
            }

            closeAiShell();

            overlay.classList.add('is-open');
            panel.classList.add('is-open');
            document.body.classList.add('fcc-help-open');
            button.setAttribute('aria-expanded', 'true');
            button.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i><span class="fcc-assist-label">Zatvori edukaciju</span>';
            if (tooltip.parentNode) {
                tooltip.parentNode.removeChild(tooltip);
            }
        }

        button.addEventListener('click', function () {
            if (isTutorialActive()) {
                return;
            }

            if (panel.classList.contains('is-open')) {
                closePanel();
            } else {
                openPanel();
            }
        });

        overlay.addEventListener('click', function () {
            closePanel();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closePanel();
            }
        });

        document.body.appendChild(overlay);
        document.body.appendChild(panel);

        dock.appendChild(button);
        if (aiToggle) {
            if (aiToggle.parentNode) {
                aiToggle.parentNode.removeChild(aiToggle);
            }
            dock.appendChild(aiToggle);
        }
        document.body.appendChild(dock);

        window.addEventListener('fcc:tutorial:state', function (event) {
            var isActive = !!(event && event.detail && event.detail.active);

            if (isActive) {
                closePanel();
                closeAiShell();
            }
        });

        if (showTooltip) {
            localStorage.setItem(storageKey, '1');
            document.body.appendChild(tooltip);
        }
    }

    onReady(function () {
        var config = findConfig();
        if (!config) {
            return;
        }
        createHelpWidget(config);
    });
})();
/* /Custom code: FC-2026-02-24 */
