<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_stats_language_name = fc_resolve_language_name(\Altum\Language::$name ?? '');
$fcc_stats_language_code = mb_strtolower((string) (\Altum\Language::$code ?? ''));
$fcc_stats_is_hr = in_array(mb_strtolower((string) $fcc_stats_language_name), ['hrvatski', 'croatian', 'hr'], true) || str_starts_with($fcc_stats_language_code, 'hr');

$top_country_code = array_key_first($data->statistics['country_code'] ?? []);
$top_country_value = $top_country_code !== null ? (($data->statistics['country_code'][$top_country_code] ?? 0)) : 0;
$top_country_label = $top_country_code ? get_country_from_country_code($top_country_code) : ($fcc_stats_is_hr ? 'Nema podataka' : 'No data');

$top_source_key = array_key_first($data->statistics['referrer_host'] ?? []);
$top_source_value = $top_source_key !== null ? (($data->statistics['referrer_host'][$top_source_key] ?? 0)) : 0;
if($top_source_key === '' || $top_source_key === null) {
    $top_source_label = $fcc_stats_is_hr ? 'Direktno' : 'Direct';
} elseif($top_source_key === 'qr') {
    $top_source_label = $fcc_stats_is_hr ? 'QR kod' : 'QR code';
} else {
    $top_source_label = $top_source_key;
}

$latest_count = is_array($data->latest ?? null) ? count($data->latest) : 0;
$latest_preview_limit = $this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page;
$overview_lead = $data->link->type === 'biolink'
    ? ($fcc_stats_is_hr ? 'Ovdje vidiš kako se ova aplikacija ponaša kroz posjete, kretanje interesa i izvore prometa u odabranom razdoblju.' : 'Here you see how this app performs through visits, movement of interest and traffic sources across the selected range.')
    : l('link.statistics.data_preview');
?>

<div class="fcc-app-overview-shell">
    <div class="fcc-app-overview-intro">
        <div>
            <div class="fcc-app-overview-intro-eyebrow"><?= $fcc_stats_is_hr ? 'FCC pregled aplikacije' : 'FCC app overview' ?></div>
            <h3><?= $data->link->type === 'biolink' ? ($fcc_stats_is_hr ? 'Ključne brojke i signali za ovu aplikaciju' : 'Key numbers and signals for this app') : l('link.statistics.overview') ?></h3>
            <p><?= $overview_lead ?></p>
        </div>
        <div class="fcc-app-overview-intro-badge">
            <span><?= $fcc_stats_is_hr ? 'Zadnje zabilježenih posjeta' : 'Latest tracked visits' ?></span>
            <strong><?= nr($latest_count) ?></strong>
        </div>
    </div>

    <div class="fcc-app-overview-kpis">
        <div class="fcc-app-overview-kpi">
            <div class="fcc-app-overview-kpi-eyebrow">
                <i class="fas fa-fw fa-eye"></i>
                <span><?= l('link.statistics.pageviews') ?></span>
            </div>
            <p class="fcc-app-overview-kpi-value"><?= nr($data->totals['pageviews']) ?></p>
            <div class="fcc-app-overview-kpi-note"><?= $fcc_stats_is_hr ? 'Ukupan broj otvaranja aplikacije u odabranom razdoblju.' : 'Total app opens in the selected period.' ?></div>
        </div>

        <div class="fcc-app-overview-kpi">
            <div class="fcc-app-overview-kpi-eyebrow">
                <i class="fas fa-fw fa-users"></i>
                <span><?= l('link.statistics.visitors') ?></span>
            </div>
            <p class="fcc-app-overview-kpi-value"><?= nr($data->totals['visitors']) ?></p>
            <div class="fcc-app-overview-kpi-note"><?= $fcc_stats_is_hr ? 'Jedinstveni ljudi koji su otvorili ovu aplikaciju.' : 'Unique people who opened this app.' ?></div>
        </div>

        <div class="fcc-app-overview-kpi">
            <div class="fcc-app-overview-kpi-eyebrow">
                <i class="fas fa-fw fa-globe-europe"></i>
                <span><?= $fcc_stats_is_hr ? 'Najjače tržište' : 'Top market' ?></span>
            </div>
            <p class="fcc-app-overview-kpi-value fcc-app-overview-kpi-value--compact"><?= htmlspecialchars((string) $top_country_label, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="fcc-app-overview-kpi-note"><?= $fcc_stats_is_hr ? 'Najviše posjeta dolazi iz ovog tržišta.' : 'Most visits currently come from this market.' ?> <strong><?= nr($top_country_value) ?></strong></div>
        </div>

        <div class="fcc-app-overview-kpi">
            <div class="fcc-app-overview-kpi-eyebrow">
                <i class="fas fa-fw fa-random"></i>
                <span><?= $fcc_stats_is_hr ? 'Glavni izvor' : 'Main source' ?></span>
            </div>
            <p class="fcc-app-overview-kpi-value fcc-app-overview-kpi-value--compact"><?= htmlspecialchars((string) $top_source_label, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="fcc-app-overview-kpi-note"><?= $fcc_stats_is_hr ? 'Ovaj kanal ti trenutno dovodi najviše interesa.' : 'This channel is currently bringing the most interest.' ?> <strong><?= nr($top_source_value) ?></strong></div>
        </div>
    </div>

    <div class="fcc-app-overview-chart-card card">
        <div class="card-body">
            <div class="fcc-app-overview-chart-head">
                <div>
                    <h3><?= $data->link->type === 'biolink' ? 'Kretanje interesa za ovu FCC aplikaciju' : l('link.statistics.overview') ?></h3>
                    <p><?= $data->link->type === 'biolink' ? ($fcc_stats_is_hr ? 'Prati ritam otvaranja i jedinstvenih posjeta. Ovaj graf ti pomaže povezati objave, kampanje i stvarni interes za aplikaciju.' : 'Track the rhythm of opens and unique visits. This chart helps connect posts, campaigns and real interest in the app.') : l('link.statistics.data_preview') ?></p>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="pageviews_chart"></canvas>
            </div>
        </div>
    </div>

    <div class="fcc-app-preview-strip" data-toggle="tooltip" title="<?= $fcc_stats_is_hr ? 'Kartice ispod koriste cijelo odabrano razdoblje. Tablica najnovijih unosa prikazuje zadnjih ' . nr($latest_preview_limit) . ' zabilježenih posjeta.' : 'The cards below use the full selected period. The latest entries table shows the last ' . nr($latest_preview_limit) . ' tracked visits.' ?>">
        <div>
            <strong><?= $fcc_stats_is_hr ? 'Pregled izvora i ponašanja' : 'Traffic and behaviour snapshot' ?></strong>
            <div class="small text-muted mt-1"><?= $data->link->type === 'biolink' ? ($fcc_stats_is_hr ? 'Kartice ispod prikazuju cijelo odabrano razdoblje, a tablica na dnu zadnje zabilježene posjete za brzi dnevni pregled.' : 'The cards below cover the full selected period, while the table at the bottom shows the latest tracked visits for a quick daily check.') : l('link.statistics.data_preview') ?></div>
        </div>
        <i class="fas fa-fw fa-info-circle text-muted"></i>
    </div>

<div class="row mb-4">
    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100 fcc-app-insight-card">
            <div class="card-body">
                <h3 class="h5"><?= l('global.continents') ?></h3>
                <div class="fcc-app-insight-subtitle"><?= $data->link->type === 'biolink' ? 'Gdje se otvara tvoja aplikacija po kontinentima.' : '&nbsp;' ?></div>

                <?php $i = 0; foreach($data->statistics['continent_code'] as $key => $value): $i++; if($i > 5) break; ?>
                    <?php $percentage = round($value / $data->statistics['continent_code_total_sum'] * 100, 1) ?>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-1">
                            <div class="text-truncate">
                                <?php if($key): ?>
                                    <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=country&continent_code=' . $key . '&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" title="<?= $key ?>" class="">
                                        <?= get_continent_from_continent_code($key) ?>
                                    </a>
                                <?php else: ?>
                                    <span class=""><?= $key ? get_continent_from_continent_code($key) : l('global.unknown') ?></span>
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
                <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=continent_code&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100 fcc-app-insight-card">
            <div class="card-body">
                <h3 class="h5"><?= l('global.countries') ?></h3>
                <div class="fcc-app-insight-subtitle"><?= $data->link->type === 'biolink' ? 'Najaktivnija tržišta za ovu aplikaciju.' : '&nbsp;' ?></div>

                <?php $i = 0; foreach($data->statistics['country_code'] as $key => $value): $i++; if($i > 5) break; ?>
                    <?php $percentage = round($value / $data->statistics['country_code_total_sum'] * 100, 1) ?>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-1">
                            <div class="text-truncate">
                                <img src="<?= ASSETS_FULL_URL . 'images/countries/' . ($key ? mb_strtolower($key) : 'unknown') . '.svg' ?>" class="img-fluid icon-favicon mr-1" />
                                <?php if($key): ?>
                                    <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=city_name&country_code=' . $key . '&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" title="<?= $key ?>" class=""><?= get_country_from_country_code($key) ?></a>
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
                <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=country&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100 fcc-app-insight-card">
            <div class="card-body">
                <h3 class="h5"><?= l('global.cities') ?></h3>
                <div class="fcc-app-insight-subtitle"><?= $data->link->type === 'biolink' ? 'Gradovi iz kojih dolazi najveći interes.' : '&nbsp;' ?></div>

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
                <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=city_name&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100 fcc-app-insight-card">
            <div class="card-body">
                <h3 class="h5"><?= l('link.statistics.referrer_host') ?></h3>
                <div class="fcc-app-insight-subtitle"><?= $data->link->type === 'biolink' ? 'Kanali i stranice koje ti dovode promet na aplikaciju.' : '&nbsp;' ?></div>

                <?php $i = 0; foreach($data->statistics['referrer_host'] as $key => $value): $i++; if($i > 5) break; ?>
                    <?php $percentage = round($value / $data->statistics['referrer_host_total_sum'] * 100, 1) ?>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-1">
                            <div class="text-truncate">
                                <?php if(!$key): ?>
                                    <span><?= l('link.statistics.referrer_direct') ?></span>
                                <?php elseif($key == 'qr'): ?>
                                    <span><?= l('link.statistics.referrer_qr') ?></span>
                                <?php else: ?>
                                    <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($key) ?>" class="img-fluid icon-favicon mr-1" loading="lazy" />
                                    <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=referrer_path&referrer_host=' . $key . '&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" title="<?= $key ?>" class=""><?= $key ?></a>
                                    <a href="<?= 'https://' . $key ?>" target="_blank" rel="nofollow noopener" class="text-muted ml-1"><i class="fas fa-fw fa-xs fa-external-link-alt"></i></a>
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
                <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=referrer_host&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100 fcc-app-insight-card">
            <div class="card-body">
                <h3 class="h5"><?= l('link.statistics.device') ?></h3>
                <div class="fcc-app-insight-subtitle"><?= $data->link->type === 'biolink' ? 'Na kojim uređajima se tvoja aplikacija najviše otvara.' : '&nbsp;' ?></div>

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
                <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=device&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100 fcc-app-insight-card">
            <div class="card-body">
                <h3 class="h5"><?= l('link.statistics.os') ?></h3>
                <div class="fcc-app-insight-subtitle"><?= $data->link->type === 'biolink' ? 'Operativni sustavi tvojih posjetitelja.' : '&nbsp;' ?></div>

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
                <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=os&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100 fcc-app-insight-card">
            <div class="card-body">
                <h3 class="h5"><?= l('link.statistics.browser') ?></h3>
                <div class="fcc-app-insight-subtitle"><?= $data->link->type === 'biolink' ? 'Preglednici kroz koje posjetitelji dolaze.' : '&nbsp;' ?></div>

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
                <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=browser&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 my-3">
        <div class="card h-100 fcc-app-insight-card">
            <div class="card-body">
                <h3 class="h5"><?= l('link.statistics.language') ?></h3>
                <div class="fcc-app-insight-subtitle"><?= $data->link->type === 'biolink' ? 'Jezici preglednika koji dominiraju u prometu.' : '&nbsp;' ?></div>

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
                <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=language&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none"><i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?></a>
            </div>
        </div>
    </div>
</div>

<div id="fcc_app_stats_tour_step_latest">
    <div class="fcc-app-latest-card card">
        <div class="card-body">
            <h3><?= l('link.statistics.latest') ?></h3>
            <div class="fcc-app-insight-subtitle"><?= $data->link->type === 'biolink' ? 'Zadnje posjete, odakle su došle i kada su se dogodile. Ovo je koristan dnevni pregled kampanja i aktivnosti.' : l('link.statistics.latest') ?></div>
            <div class="small text-muted mb-3"><?= $fcc_stats_is_hr ? 'Prikazano je zadnjih ' . nr($latest_preview_limit) . ' zabilježenih posjeta u odabranom razdoblju.' : 'Showing the last ' . nr($latest_preview_limit) . ' tracked visits in the selected period.' ?></div>

            <div class="table-responsive table-custom-container">
                <table class="table table-custom">
                    <thead>
                    <tr>
                        <th class="">
                            <div><?= l('global.country') ?></div>
                            <div><?= l('global.city') ?></div>
                        </th>
                        <th class=""><?= l('link.table.device') ?></th>
                        <th class="">
                            <div><?= l('link.table.os') ?></div>
                            <div><?= l('link.table.browser') ?></div>
                        </th>
                        <th class=""><?= l('link.table.referrer') ?></th>
                        <th class=""><?= l('global.datetime') ?></th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php $i = 1; ?>
                    <?php foreach($data->latest as $row): ?>
                        <?php if($i++ > 10) break ?>
                        <tr>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center">
                                    <div class="table-image-wrapper mr-3">
                                        <img src="<?= ASSETS_FULL_URL . 'images/countries/' . ($row->country_code ? mb_strtolower($row->country_code) : 'unknown') . '.svg' ?>" class="img-fluid icon-favicon" />
                                    </div>

                                    <div class="d-flex flex-column">
                                        <span class=""><?= $row->country_code ? get_country_from_country_code($row->country_code) : l('global.unknown') ?></span>
                                        <span class="text-muted small"><?= $row->city_name ?? l('global.unknown') ?></span>
                                    </div>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <span class="badge badge-light">
                                    <?= $row->device_type ? '<i class="fas fa-fw fa-sm fa-' . $row->device_type . ' mr-1"></i>' . l('global.device.' . $row->device_type) : l('global.unknown') ?>
                                </span>
                            </td>

                            <td class="text-nowrap">
                                <div>
                                    <img src="<?= ASSETS_FULL_URL . 'images/os/' . os_name_to_os_key($row->os_name) . '.svg' ?>" class="img-fluid icon-favicon-small mr-1" />
                                    <span class="font-size-small"><?= $row->os_name ?: l('global.unknown') ?></span>
                                </div>
                                <div>
                                    <img src="<?= ASSETS_FULL_URL . 'images/browsers/' . browser_name_to_browser_key($row->browser_name) . '.svg' ?>" class="img-fluid icon-favicon-small mr-1" />
                                    <span class="font-size-small"><?= $row->browser_name ?: l('global.unknown') ?></span>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <?php if(!$row->referrer_host): ?>
                                    <span><?= l('link.statistics.referrer_direct') ?></span>
                                <?php elseif($row->referrer_host == 'qr'): ?>
                                    <span><?= l('link.statistics.referrer_qr') ?></span>
                                <?php else: ?>
                                    <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($row->referrer_host) ?>" class="img-fluid icon-favicon mr-1" loading="lazy" />
                                    <a href="<?= url($data->url . '/statistics?type=referrer_path&referrer_host=' . $row->referrer_host . '&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" title="<?= $row->referrer_host ?>" class=""><?= $row->referrer_host ?></a>
                                    <a href="<?= 'https://' . $row->referrer_host ?>" target="_blank" rel="nofollow noopener" class="text-muted ml-1"><i class="fas fa-fw fa-xs fa-external-link-alt"></i></a>
                                <?php endif ?>
                            </td>

                            <td class="text-nowrap">
                                <span class="text-muted" data-toggle="tooltip" title="<?= \Altum\Date::get($row->datetime, 1) ?>"><?= \Altum\Date::get_timeago($row->datetime) ?></span>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <tr>
                        <td colspan="7">
                            <a href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=entries&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>" class="text-muted text-decoration-none small">
                                <i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?>
                            </a>
                        </td>
                    </tr>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<?php require THEME_PATH . 'views/partials/js_chart_defaults.php' ?>

<?php ob_start() ?>

<script>
    'use strict';

    /* Charts */
    <?php if (!empty($data->pageviews)): ?>
    let css = window.getComputedStyle(document.body);
    let pageviews_color = css.getPropertyValue('--primary');
    let visitors_color = css.getPropertyValue('--gray-400');
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
            labels: <?= $data->pageviews_chart['labels'] ?>,
            datasets: [
                {
                    label: <?= json_encode(l('link.statistics.pageviews')) ?>,
                    data: <?= $data->pageviews_chart['pageviews'] ?? '[]' ?>,
                    backgroundColor: pageviews_color_gradient,
                    borderColor: pageviews_color,
                    fill: true
                },
                {
                    label: <?= json_encode(l('link.statistics.visitors')) ?>,
                    data: <?= $data->pageviews_chart['visitors'] ?? '[]' ?>,
                    backgroundColor: visitors_color_gradient,
                    borderColor: visitors_color,
                    fill: true
                }
            ]
        },
        options: chart_options
    });

    <?php endif ?>
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
