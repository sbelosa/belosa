<?php defined('ALTUMCODE') || die() ?>

<div class="card mb-5">
    <div class="card-body">
        <div class="chart-container">
            <canvas id="subscribers_chart"></canvas>
        </div>
    </div>
</div>

<div class="d-flex align-items-center">
    <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3" data-toggle="tooltip" title="<?= sprintf(l('subscribers_statistics.data_preview_info'), $this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page) ?>">
        <i class="fas fa-fw fa-sm fa-info-circle mr-1"></i> <?= l('subscribers_statistics.data_preview') ?>
    </h2>

    <div class="flex-fill">
        <hr class="border-gray-100" />
    </div>
</div>

<div class="row mb-4">
    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h5"><?= l('global.countries') ?></h3>
                <p></p>

                <?php $i = 0; foreach($data->statistics['country_code'] as $key => $value): $i++; if($i > 5) break; ?>
                    <?php $percentage = round($value / $data->statistics['country_code_total_sum'] * 100, 1) ?>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-1">
                            <div class="text-truncate">
                                <img src="<?= ASSETS_FULL_URL . 'images/countries/' . ($key ? mb_strtolower($key) : 'unknown') . '.svg' ?>" class="img-fluid icon-favicon mr-1" />
                                <?php if($key): ?>
                                    <a href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=city_name&country_code=' . $key . '&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" title="<?= $key ?>" class=""><?= get_country_from_country_code($key) ?></a>
                                <?php else: ?>
                                    <span class=""><?= $key ? get_country_from_country_code($key) : l('global.unknown') ?></span>
                                <?php endif ?>
                            </div>

                            <div>
                                <small class="text-muted"><?= nr($percentage, 2, false) . '%' ?></small>
                                <span class="ml-3"><?= nr($value) ?></span>
                            </div>
                        </div>

                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="card-body small py-3 d-flex align-items-end">
                <a href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=country&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h5"><?= l('global.cities') ?></h3>
                <p></p>

                <?php $i = 0; foreach($data->statistics['city_name'] as $key => $value): $i++; if($i > 5) break; ?>
                    <?php $percentage = round($value / $data->statistics['city_name_total_sum'] * 100, 1) ?>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-1">
                            <div class="text-truncate">
                                <span class=""><?= $key ? $key : l('global.unknown') ?></span>
                            </div>

                            <div>
                                <small class="text-muted"><?= nr($percentage, 2, false) . '%' ?></small>
                                <span class="ml-3"><?= nr($value) ?></span>
                            </div>
                        </div>

                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="card-body small py-3 d-flex align-items-end">
                <a href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=city_name&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h5"><?= l('subscribers_statistics.device') ?></h3>
                <p></p>

                <?php $i = 0; foreach($data->statistics['device_type'] as $key => $value): $i++; if($i > 5) break; ?>
                    <?php $percentage = round($value / $data->statistics['device_type_total_sum'] * 100, 1) ?>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-1">
                            <div class="text-truncate">
                                <?php if(!$key): ?>
                                    <span><?= l('global.unknown') ?></span>
                                <?php else: ?>
                                    <span><i class="fas fa-fw fa-sm fa-<?= $key ?> text-muted mr-1"></i> <?= l('global.device.' . $key) ?></span>
                                <?php endif ?>
                            </div>

                            <div>
                                <small class="text-muted"><?= nr($percentage, 2, false) . '%' ?></small>
                                <span class="ml-3"><?= nr($value) ?></span>
                            </div>
                        </div>

                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="card-body small py-3 d-flex align-items-end">
                <a href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=device&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h5"><?= l('subscribers_statistics.os') ?></h3>
                <p></p>

                <?php $i = 0; foreach($data->statistics['os_name'] as $key => $value): $i++; if($i > 5) break; ?>
                    <?php $percentage = round($value / $data->statistics['os_name_total_sum'] * 100, 1) ?>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-1">
                            <div class="text-truncate">
                                <img src="<?= ASSETS_FULL_URL . 'images/os/' . os_name_to_os_key($key) . '.svg' ?>" class="img-fluid icon-favicon mr-1" />
                                <span class=""><?= $key ?:  l('global.unknown') ?></span>
                            </div>

                            <div>
                                <small class="text-muted"><?= nr($percentage, 2, false) . '%' ?></small>
                                <span class="ml-3"><?= nr($value) ?></span>
                            </div>
                        </div>

                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="card-body small py-3 d-flex align-items-end">
                <a href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=os&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h5"><?= l('subscribers_statistics.browser') ?></h3>
                <p></p>

                <?php $i = 0; foreach($data->statistics['browser_name'] as $key => $value): $i++; if($i > 5) break; ?>
                    <?php $percentage = round($value / $data->statistics['browser_name_total_sum'] * 100, 1) ?>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-1">
                            <div class="text-truncate">
                                <img src="<?= ASSETS_FULL_URL . 'images/browsers/' . browser_name_to_browser_key($key) . '.svg' ?>" class="img-fluid icon-favicon mr-1" />
                                <span class=""><?= $key ?:  l('global.unknown') ?></span>
                            </div>

                            <div>
                                <small class="text-muted"><?= nr($percentage, 2, false) . '%' ?></small>
                                <span class="ml-3"><?= nr($value) ?></span>
                            </div>
                        </div>

                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="card-body small py-3 d-flex align-items-end">
                <a href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=browser&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h5"><?= l('subscribers_statistics.language') ?></h3>
                <p></p>

                <?php $i = 0; foreach($data->statistics['browser_language'] as $key => $value): $i++; if($i > 5) break; ?>
                    <?php $percentage = round($value / $data->statistics['browser_language_total_sum'] * 100, 1) ?>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-1">
                            <div class="text-truncate">
                                <?php if(!$key): ?>
                                    <span><?= l('global.unknown') ?></span>
                                <?php else: ?>
                                    <span><?= get_language_from_locale($key) ?></span>
                                <?php endif ?>
                            </div>

                            <div>
                                <small class="text-muted"><?= nr($percentage, 2, false) . '%' ?></small>
                                <span class="ml-3"><?= nr($value) ?></span>
                            </div>
                        </div>

                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="card-body small py-3 d-flex align-items-end">
                <a href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=language&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>
</div>

<div class="card h-100">
    <div class="card-body">
        <h3 class="h5"><?= l('subscribers_statistics.subscribed_on_url') ?></h3>
        <p></p>

        <?php $i = 0; foreach($data->statistics['subscribed_on_url'] as $key => $value): $i++; if($i > 5) break; ?>
            <?php $percentage = round($value / $data->statistics['subscribed_on_url_total_sum'] * 100, 1) ?>

            <div class="mt-4">
                <div class="d-flex justify-content-between mb-1">
                    <div class="text-truncate">
                        <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($key) ?>" class="img-fluid icon-favicon mr-1" loading="lazy" />
                       <?= remove_url_protocol_from_url($key) ?>
                        <a href="<?= $key ?>" target="_blank" rel="nofollow noopener" class="text-muted ml-1"><i class="fas fa-fw fa-xs fa-external-link-alt"></i></a>
                    </div>

                    <div>
                        <small class="text-muted"><?= nr($percentage, 2, false) . '%' ?></small>
                        <span class="ml-3"><?= nr($value) ?></span>
                    </div>
                </div>

                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <div class="card-body small py-3 d-flex align-items-end">
        <a href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=language&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
    </div>
</div>

<?php require THEME_PATH . 'views/partials/js_chart_defaults.php' ?>

<?php ob_start() ?>

    <script>
        'use strict';

        <?php if($data->has_data): ?>
        let css = window.getComputedStyle(document.body);
        let subscribers_color = css.getPropertyValue('--primary');
        let subscribers_color_gradient = null;

        /* Chart */
        let subscribers_chart = document.getElementById('subscribers_chart').getContext('2d');

        /* Colors */
        subscribers_color_gradient = subscribers_chart.createLinearGradient(0, 0, 0, 250);
        subscribers_color_gradient.addColorStop(0, set_hex_opacity(subscribers_color, 0.6));
        subscribers_color_gradient.addColorStop(1, set_hex_opacity(subscribers_color, 0.1));

        /* Display chart */
        new Chart(subscribers_chart, {
            type: 'line',
            data: {
                labels: <?= $data->subscribers_chart['labels'] ?>,
                datasets: [
                    {
                        label: <?= json_encode(l('websites.subscribers')) ?>,
                        data: <?= $data->subscribers_chart['total'] ?? '[]' ?>,
                        backgroundColor: subscribers_color_gradient,
                        borderColor: subscribers_color,
                        fill: true
                    }
                ]
            },
            options: chart_options
        });
        <?php endif ?>
    </script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
