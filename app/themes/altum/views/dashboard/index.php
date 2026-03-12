<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(!empty($data->needs_fcc_education)): ?>
        <!-- Custom code: FC-2026-02-24: FCC core education banner -->
        <div class="alert alert-warning d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
            <div class="mb-3 mb-md-0">
                <div class="font-weight-bold mb-1">
                    <?= l('fcc_education.banner_title') ?>
                </div>
                <div><?= l('fcc_education.banner_text') ?></div>
            </div>
            <a class="btn btn-dark" href="<?= url('fcc-education') ?>">
                <?= l('fcc_education.banner_button') ?>
            </a>
        </div>
        <!-- /Custom code: FC-2026-02-24 -->
    <?php endif ?>

    <?php
        /* Custom code: FC-2026-03-03: hidden dashboard link type cards for all users */
        $show_admin_only_dashboard_link_cards = false;

    $enabled_links = [];
    if(settings()->links->biolinks_is_enabled) $enabled_links[] = 'biolink';
    if(settings()->links->shortener_is_enabled) $enabled_links[] = 'link';
        if(settings()->links->files_is_enabled && $show_admin_only_dashboard_link_cards) $enabled_links[] = 'file';
        if(settings()->links->vcards_is_enabled && $show_admin_only_dashboard_link_cards) $enabled_links[] = 'vcard';
    if(settings()->links->events_is_enabled) $enabled_links[] = 'event';
        if(settings()->links->static_is_enabled && $show_admin_only_dashboard_link_cards) $enabled_links[] = 'static';
    $enabled_links_count = count($enabled_links);

    $col_class = match ($enabled_links_count) {
        1 => 'col-12',
        2,4 => 'col-12 col-sm-6',
        3,5,6 => 'col-12 col-sm-6 col-xl-4',
        default => null,
    }
    ?>

    <div class="mb-5">
        <div class="row m-n3 justify-content-between">
            <?php if(settings()->links->biolinks_is_enabled): ?>
                <div class="<?= $col_class ?> p-3">
                    <div class="card h-100 position-relative">
                        <div class="card-body d-flex">
                            <div>
                                <div class="card border-0 mr-3 position-static" style="background: #eff6ff;">
                                    <div class="p-3 d-flex align-items-center justify-content-between">
                                        <a href="<?= url('links?type=biolink') ?>" class="stretched-link" style="color: #3b82f6;">
                                            <i class="fas fa-fw fa-hashtag fa-lg"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="card-title h4 m-0" id="biolink_links_total">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                                <span class="text-muted"><?= l('dashboard.biolinks') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <?php if(settings()->links->shortener_is_enabled): ?>
                <div class="<?= $col_class ?> p-3">
                    <div class="card h-100 position-relative">
                        <div class="card-body d-flex">
                            <div>
                                <div class="card border-0 mr-3 position-static" style="background: #f0fdfa;">
                                    <div class="p-3 d-flex align-items-center justify-content-between">
                                        <a href="<?= url('links?type=link') ?>" class="stretched-link" style="color: #14b8a6;">
                                            <i class="fas fa-fw fa-link fa-lg"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="card-title h4 m-0" id="link_links_total">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                                <span class="text-muted"><?= l('dashboard.links') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <?php if(settings()->links->files_is_enabled && $show_admin_only_dashboard_link_cards): ?>
                <div class="<?= $col_class ?> p-3">
                    <div class="card h-100 position-relative">
                        <div class="card-body d-flex">
                            <div>
                                <div class="card border-0 mr-3 position-static" style="background: #ecfdf5;">
                                    <div class="p-3 d-flex align-items-center justify-content-between">
                                        <a href="<?= url('links?type=file') ?>" class="stretched-link" style="color: #10b981;">
                                            <i class="fas fa-fw fa-file fa-lg"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="card-title h4 m-0" id="file_links_total">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                                <span class="text-muted"><?= l('dashboard.file_links') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <?php if(settings()->links->vcards_is_enabled && $show_admin_only_dashboard_link_cards): ?>
                <div class="<?= $col_class ?> p-3">
                    <div class="card h-100 position-relative">
                        <div class="card-body d-flex">
                            <div>
                                <div class="card border-0 mr-3 position-static" style="background: #ecfeff;">
                                    <div class="p-3 d-flex align-items-center justify-content-between">
                                        <a href="<?= url('links?type=vcard') ?>" class="stretched-link" style="color: #06b6d4;">
                                            <i class="fas fa-fw fa-id-card fa-lg"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="card-title h4 m-0" id="vcard_links_total">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                                <span class="text-muted"><?= l('dashboard.vcard_links') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <?php if(settings()->links->events_is_enabled): ?>
                <div class="<?= $col_class ?> p-3">
                    <div class="card h-100 position-relative">
                        <div class="card-body d-flex">
                            <div>
                                <div class="card border-0 mr-3 position-static" style="background: #eef2ff;">
                                    <div class="p-3 d-flex align-items-center justify-content-between">
                                        <a href="<?= url('links?type=event') ?>" class="stretched-link" style="color: #6366f1;">
                                            <i class="fas fa-fw fa-calendar fa-lg"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="card-title h4 m-0" id="event_links_total">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                                <span class="text-muted"><?= l('dashboard.event_links') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <?php if(settings()->links->static_is_enabled && $show_admin_only_dashboard_link_cards): ?>
                <div class="<?= $col_class ?> p-3">
                    <div class="card h-100 position-relative">
                        <div class="card-body d-flex">
                            <div>
                                <div class="card border-0 mr-3 position-static" style="background: #fdf4ff;">
                                    <div class="p-3 d-flex align-items-center justify-content-between">
                                        <a href="<?= url('links?type=static') ?>" class="stretched-link" style="color: #c026d3;">
                                            <i class="fas fa-fw fa-file-code fa-lg"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="card-title h4 m-0" id="static_links_total">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                                <span class="text-muted"><?= l('dashboard.static_links') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif ?>
        </div>

        <!-- Custom code: FC-2026-03-05: detailed forever analytics dashboard section -->
        <div class="mt-4">
            <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
                <h2 class="h5 mb-2 mb-md-0"><i class="fas fa-fw fa-sm fa-chart-line text-primary mr-1"></i> <?= l('dashboard.forever_analytics.header') ?></h2>
                <small class="text-muted"><?= l('dashboard.forever_analytics.subheader') ?></small>
            </div>

            <div class="row m-n2 mb-2">
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.track_clicks_total') ?></small><div class="h5 mb-0" id="dashboard_track_clicks_total"><span class="spinner-border spinner-border-sm" role="status"></span></div><small class="text-muted d-block mt-1"><?= l('dashboard.forever_analytics.unique') ?>: <span id="dashboard_track_clicks_unique_total"><span class="spinner-border spinner-border-sm" role="status"></span></span></small></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.track_clicks_30d') ?></small><div class="h5 mb-0" id="dashboard_track_clicks_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div><small class="text-muted d-block mt-1"><?= l('dashboard.forever_analytics.unique') ?>: <span id="dashboard_track_clicks_unique_30d"><span class="spinner-border spinner-border-sm" role="status"></span></span></small></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.biolink_visits_30d') ?></small><div class="h5 mb-0" id="dashboard_biolink_visits_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="card h-100">
                        <div class="card-body">
                            <small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.current_package') ?></small>
                            <div class="h5 mb-0" id="dashboard_current_package_name"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                            <small class="text-muted d-block mt-1"><?= l('dashboard.forever_analytics.package_active_until') ?>: <span id="dashboard_package_active_until"><span class="spinner-border spinner-border-sm" role="status"></span></span></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row m-n2 mb-2">
                <div class="col-12 col-md-6 p-2">
                    <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.forever_shop_clicks_30d') ?></small><div class="h5 mb-0" id="dashboard_forever_shop_clicks_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
                </div>
                <div class="col-12 col-md-6 p-2">
                    <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.forever_registration_clicks_30d') ?></small><div class="h5 mb-0" id="dashboard_forever_registration_clicks_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
                </div>
            </div>

            <div class="row m-n2">
                <div class="col-12 col-xl-6 p-2">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="h6 mb-3"><?= l('dashboard.forever_analytics.top_countries_30d') ?></h3>
                            <div id="dashboard_top_countries_30d" class="small text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                            <div id="dashboard_top_countries_30d_toggle" class="pt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6 p-2">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="h6 mb-3"><?= l('dashboard.forever_analytics.top_forever_pages_30d') ?></h3>
                            <div id="dashboard_top_forever_pages_30d" class="small text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                            <div id="dashboard_top_forever_pages_30d_toggle" class="pt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Custom code: FC-2026-03-05 -->

        <div class="card mt-5">
            <div class="card-body">
                <div class="chart-container d-none" id="pageviews_chart_container">
                    <canvas id="pageviews_chart"></canvas>
                </div>

                <div id="pageviews_chart_no_data" class="d-none">
                    <?= include_view(THEME_PATH . 'views/partials/no_chart_data.php', ['has_wrapper' => false]); ?>
                </div>

                <div id="pageviews_chart_loading" class="chart-container d-flex align-items-center justify-content-center">
                    <span class="spinner-border spinner-border-lg" role="status"></span>
                </div>

                <?php if(settings()->main->chart_cache): ?>
                <small class="text-muted d-none" id="pageviews_chart_help">
                    <span data-toggle="tooltip" title="<?= sprintf(l('global.chart_help'), settings()->main->chart_cache ?? 12, settings()->main->chart_days ?? 30) ?>"><i class="fas fa-fw fa-sm fa-info-circle mr-1"></i></span>
                    <span class="d-lg-none"><?= sprintf(l('global.chart_help'), settings()->main->chart_cache ?? 12, settings()->main->chart_days ?? 30) ?></span>
                </small>
                <?php endif ?>
            </div>
        </div>

        <?php require THEME_PATH . 'views/partials/js_chart_defaults.php' ?>
    </div>

    <?= $this->views['links_content'] ?>
</div>

<?php ob_start() ?>
    <script>
    'use strict';

        const dashboard_compact_state = {};
        const render_dashboard_compact_list = (container_selector, toggle_selector, items_html, visible_limit = 5) => {
            const container = document.querySelector(container_selector);
            const toggle_container = document.querySelector(toggle_selector);

            if(!container || !toggle_container) {
                return;
            }

            if(!Array.isArray(items_html) || !items_html.length) {
                container.innerHTML = `<span class="text-muted"><?= l('global.no_data') ?></span>`;
                toggle_container.innerHTML = '';
                return;
            }

            if(dashboard_compact_state[container_selector] === undefined) {
                dashboard_compact_state[container_selector] = false;
            }

            const is_expanded = dashboard_compact_state[container_selector];
            const should_toggle = items_html.length > visible_limit;
            const visible_items = should_toggle && !is_expanded ? items_html.slice(0, visible_limit) : items_html;

            container.innerHTML = visible_items.join('');

            if(!should_toggle) {
                toggle_container.innerHTML = '';
                return;
            }

            toggle_container.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-secondary" data-dashboard-compact-toggle="${container_selector}">
                    ${is_expanded ? '<?= l('dashboard.compact_list.show_less') ?>' : '<?= l('global.view_more') ?>'}
                </button>
            `;

            const toggle_button = toggle_container.querySelector('[data-dashboard-compact-toggle]');
            if(toggle_button) {
                toggle_button.addEventListener('click', () => {
                    dashboard_compact_state[container_selector] = !dashboard_compact_state[container_selector];
                    render_dashboard_compact_list(container_selector, toggle_selector, items_html, visible_limit);
                });
            }
        };
    
        (async function fetch_statistics() {
            /* Send request to server */
            let response = await fetch(`${url}dashboard/get_stats_ajax`, {
                method: 'get',
            });

            let data = null;
            try {
                data = await response.json();
            } catch (error) {
                /* :)  */
            }

            if(!response.ok) {
                /* :)  */
            }

            if(data.status == 'error') {
                /* :)  */
            } else if(data.status == 'success') {

                /* update link_links_total */
                const link_links_total_element = document.querySelector('#link_links_total');
                if (link_links_total_element) {
                    link_links_total_element.innerHTML = data.details.link_links_total ? nr(data.details.link_links_total) : 0;
                }

                /* update file_links_total */
                const file_links_total_element = document.querySelector('#file_links_total');
                if (file_links_total_element) {
                    file_links_total_element.innerHTML = data.details.file_links_total ? nr(data.details.file_links_total) : 0;
                }

                /* update vcard_links_total */
                const vcard_links_total_element = document.querySelector('#vcard_links_total');
                if (vcard_links_total_element) {
                    vcard_links_total_element.innerHTML = data.details.vcard_links_total ? nr(data.details.vcard_links_total) : 0;
                }

                /* update biolink_links_total */
                const biolink_links_total_element = document.querySelector('#biolink_links_total');
                if (biolink_links_total_element) {
                    biolink_links_total_element.innerHTML = data.details.biolink_links_total ? nr(data.details.biolink_links_total) : 0;
                }

                /* update event_links_total */
                const event_links_total_element = document.querySelector('#event_links_total');
                if (event_links_total_element) {
                    event_links_total_element.innerHTML = data.details.event_links_total ? nr(data.details.event_links_total) : 0;
                }

                /* update static_links_total */
                const static_links_total_element = document.querySelector('#static_links_total');
                if (static_links_total_element) {
                    static_links_total_element.innerHTML = data.details.static_links_total ? nr(data.details.static_links_total) : 0;
                }

                /* Custom code: FC-2026-03-05: render detailed forever analytics */
                const dashboard_forever_analytics = data.details.dashboard_forever_analytics ?? {};

                const set_metric = (selector, value) => {
                    const element = document.querySelector(selector);
                    if(element) {
                        element.innerHTML = nr(value ?? 0);
                    }
                };

                set_metric('#dashboard_track_clicks_total', dashboard_forever_analytics.track_clicks_total);
                set_metric('#dashboard_track_clicks_unique_total', dashboard_forever_analytics.track_clicks_unique_total);
                set_metric('#dashboard_track_clicks_30d', dashboard_forever_analytics.track_clicks_30d);
                set_metric('#dashboard_track_clicks_unique_30d', dashboard_forever_analytics.track_clicks_unique_30d);
                set_metric('#dashboard_biolink_visits_30d', dashboard_forever_analytics.biolink_visits_30d);
                set_metric('#dashboard_forever_shop_clicks_30d', dashboard_forever_analytics.forever_shop_clicks_30d);
                set_metric('#dashboard_forever_registration_clicks_30d', dashboard_forever_analytics.forever_registration_clicks_30d);

                const dashboard_current_package_name = document.querySelector('#dashboard_current_package_name');
                if(dashboard_current_package_name) {
                    dashboard_current_package_name.innerText = dashboard_forever_analytics.current_package_name ?? '-';
                }

                const dashboard_package_active_until = document.querySelector('#dashboard_package_active_until');
                if(dashboard_package_active_until) {
                    dashboard_package_active_until.innerText = dashboard_forever_analytics.package_active_until ?? '<?= l('global.na') ?>';
                }

                const top_countries_html = (dashboard_forever_analytics.top_countries_30d ?? []).map(country => {
                    return `
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>${country.country_code ? `${country.country_code}` : '-'}</span>
                            <strong>${nr(country.total ?? 0)}</strong>
                        </div>
                    `;
                });

                render_dashboard_compact_list('#dashboard_top_countries_30d', '#dashboard_top_countries_30d_toggle', top_countries_html, 5);

                const top_forever_pages_html = (dashboard_forever_analytics.top_forever_pages_30d ?? []).map(item => {
                    const page_url = item.url ? `${url}${item.url}` : null;
                    return `
                        <div class="border-bottom py-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-truncate mr-2" style="max-width: 85%;">${page_url ? `<a href="${page_url}" target="_blank" rel="noopener noreferrer">${item.url}</a>` : '<?= l('global.unknown') ?>'}</span>
                                <strong>${nr(item.total ?? 0)}</strong>
                            </div>
                        </div>
                    `;
                });

                render_dashboard_compact_list('#dashboard_top_forever_pages_30d', '#dashboard_top_forever_pages_30d_toggle', top_forever_pages_html, 5);
                /* /Custom code: FC-2026-03-05 */

                /* Remove loading */
                document.querySelector('#pageviews_chart_loading').classList.add('d-none');
                document.querySelector('#pageviews_chart_loading').classList.remove('d-flex');

                /* Chart */
                if(data.details.links_chart.is_empty) {
                    document.querySelector('#pageviews_chart_no_data').classList.remove('d-none');
                } else {
                    /* Display chart data */
                    document.querySelector('#pageviews_chart_container').classList.remove('d-none');
                    document.querySelector('#pageviews_chart_help') && document.querySelector('#pageviews_chart_help').classList.remove('d-none');

                    let css = window.getComputedStyle(document.body);
                    let pageviews_color = css.getPropertyValue('--primary');
                    let visitors_color = css.getPropertyValue('--gray-300');
                    let pageviews_color_gradient = null;
                    let visitors_color_gradient = null;

                    /* Chart */
                    let pageviews_chart = document.getElementById('pageviews_chart').getContext('2d');

                    /* Colors */
                    pageviews_color_gradient = pageviews_chart.createLinearGradient(0, 0, 0, 250);
                    pageviews_color_gradient.addColorStop(0, set_hex_opacity(pageviews_color, 0.6));
                    pageviews_color_gradient.addColorStop(1, set_hex_opacity(pageviews_color, 0.1));

                    visitors_color_gradient = pageviews_chart.createLinearGradient(0, 0, 0, 250);
                    visitors_color_gradient.addColorStop(0, set_hex_opacity(visitors_color, 0.6));
                    visitors_color_gradient.addColorStop(1, set_hex_opacity(visitors_color, 0.1));

                    new Chart(pageviews_chart, {
                        type: 'line',
                        data: {
                            labels: JSON.parse(data.details.links_chart.labels ?? '[]'),
                            datasets: [
                                {
                                    label: <?= json_encode(l('link.statistics.pageviews')) ?>,
                                    data: JSON.parse(data.details.links_chart.pageviews ?? '[]'),
                                    backgroundColor: pageviews_color_gradient,
                                    borderColor: pageviews_color,
                                    fill: true
                                },
                                {
                                    label: <?= json_encode(l('link.statistics.visitors')) ?>,
                                    data: JSON.parse(data.details.links_chart.visitors ?? '[]'),
                                    backgroundColor: visitors_color_gradient,
                                    borderColor: visitors_color,
                                    fill: true
                                }
                            ]
                        },
                        options: chart_options
                    });
                }
            }
        })();
    </script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
