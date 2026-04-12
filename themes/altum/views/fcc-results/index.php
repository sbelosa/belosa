<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-03-14: FCC results page layout -->
<style>
    .fcc-results-page .fcc-hero-card,
    .fcc-results-page .fcc-card {
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 14px;
        background: linear-gradient(160deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
    }

    .fcc-results-page .fcc-hero-card {
        background:
            radial-gradient(1200px 280px at -8% -60%, rgba(13, 148, 136, 0.18), transparent 65%),
            linear-gradient(155deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
    }

    .fcc-results-page .fcc-title {
        letter-spacing: 0.2px;
    }

    .fcc-results-page .fcc-period-panel {
        min-width: 248px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    .fcc-results-page .fcc-hero-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.9rem;
    }

    .fcc-results-page .fcc-qualification-text {
        margin-bottom: 0;
    }

    .fcc-results-page .fcc-segmented {
        display: flex;
        width: auto;
        padding: 5px;
        border-radius: 18px;
        background:
            linear-gradient(180deg, rgba(9, 31, 30, 0.94), rgba(7, 20, 20, 0.9));
        border: 1px solid rgba(95, 241, 224, 0.2);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04), 0 14px 30px rgba(0, 0, 0, 0.18);
    }

    .fcc-results-page .fcc-segmented .btn {
        flex: 1 1 0;
        min-width: 110px;
        min-height: 44px;
        border: 0 !important;
        border-radius: 14px !important;
        font-weight: 700;
        font-size: 15px;
        letter-spacing: 0.01em;
        transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .fcc-results-page .fcc-segmented .btn-outline-primary {
        color: #93ddd3;
        background: transparent;
        box-shadow: none;
    }

    .fcc-results-page .fcc-segmented .btn-outline-primary:hover,
    .fcc-results-page .fcc-segmented .btn-outline-primary:focus {
        color: #ddfffb;
        background: rgba(127, 215, 208, 0.08);
    }

    .fcc-results-page .fcc-segmented .btn-primary {
        color: #07302d;
        background: linear-gradient(135deg, #bbfff6 0%, #7df3e6 22%, #4fded7 60%, #2ecfca 100%);
        box-shadow: 0 10px 24px rgba(46, 207, 202, 0.28);
    }

    .fcc-results-page .fcc-segmented .btn-primary:hover,
    .fcc-results-page .fcc-segmented .btn-primary:focus {
        color: #07302d;
        background: linear-gradient(135deg, #cafff8 0%, #89f6ea 22%, #5ae3db 60%, #35d4ce 100%);
    }

    @media (max-width: 991.98px) {
        .fcc-results-page .fcc-hero-top {
            flex-direction: column;
            align-items: stretch;
        }

        .fcc-results-page .fcc-period-panel {
            width: 100%;
            min-width: 0;
            justify-content: flex-start;
        }

        .fcc-results-page .fcc-segmented {
            width: 100%;
        }
    }

    .fcc-results-page .fcc-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        margin-right: 0.25rem;
        margin-top: 0.2rem;
    }

    .fcc-results-page .fcc-chip-qualified { background: rgba(20, 184, 166, 0.17); color: #9ff3e7; }
    .fcc-results-page .fcc-chip-top { background: rgba(245, 158, 11, 0.16); color: #fcd68a; }
    .fcc-results-page .fcc-chip-rising { background: rgba(34, 197, 94, 0.2); color: #92f6ba; }
    .fcc-results-page .fcc-chip-falling { background: rgba(239, 68, 68, 0.2); color: #ffb0b0; }

    .fcc-results-page .fcc-stat-breakdown {
        margin-top: -0.35rem;
        margin-bottom: 0.55rem;
        padding-left: 0.1rem;
        color: rgba(226, 232, 240, 0.7);
        font-size: 12px;
        line-height: 1.45;
    }

    .fcc-results-page .fcc-table th {
        border-top: 0;
        font-size: 12px;
        letter-spacing: 0.25px;
        text-transform: uppercase;
    }

    .fcc-results-page .fcc-table td,
    .fcc-results-page .fcc-table th {
        border-color: rgba(255, 255, 255, 0.08);
        vertical-align: middle;
    }

    .fcc-results-page .fcc-table tr.table-active {
        background: rgba(13, 148, 136, 0.12);
    }

    .fcc-results-page .fcc-table tbody tr:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .fcc-results-page .fcc-stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 0.55rem 0;
    }

    .fcc-results-page .fcc-stat-row:last-child {
        border-bottom: 0;
    }

    .fcc-results-page .fcc-help-icon {
        color: rgba(255, 255, 255, 0.55);
        font-size: 11px;
        margin-left: 0.35rem;
        cursor: help;
    }

    .fcc-results-page .fcc-hero-note {
        color: #d6fff8;
        background: rgba(13, 148, 136, 0.16);
        border: 1px solid rgba(127, 215, 208, 0.16);
        border-radius: 12px;
        padding: 0.75rem 0.9rem;
    }

    .fcc-results-page .fcc-hero-note a {
        color: #d9fff9;
        text-decoration: underline;
        font-weight: 700;
    }

    .fcc-results-page .fcc-metric-note {
        color: rgba(223, 255, 251, 0.82);
        background: rgba(127, 215, 208, 0.07);
        border: 1px solid rgba(127, 215, 208, 0.12);
        border-radius: 12px;
        padding: 0.7rem 0.85rem;
    }

    .fcc-results-page .fcc-ai-widget {
        background:
            radial-gradient(520px 180px at 115% -5%, rgba(34, 211, 238, 0.14), transparent 62%),
            radial-gradient(460px 220px at -10% 110%, rgba(45, 212, 191, 0.12), transparent 66%),
            linear-gradient(160deg, rgba(11, 36, 44, 0.94), rgba(10, 22, 34, 0.94));
        border: 1px solid rgba(127, 215, 208, 0.16);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.03), 0 18px 40px rgba(0, 0, 0, 0.16);
    }

    .fcc-results-page .fcc-ai-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.32rem 0.7rem;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        border: 1px solid rgba(255,255,255,0.08);
    }

    .fcc-results-page .fcc-ai-pill-starter {
        background: rgba(59, 130, 246, 0.14);
        color: #b9dcff;
    }

    .fcc-results-page .fcc-ai-pill-active {
        background: rgba(34, 197, 94, 0.16);
        color: #bbf7d0;
    }

    .fcc-results-page .fcc-ai-pill-vip {
        background: rgba(250, 204, 21, 0.14);
        color: #fde68a;
    }

    .fcc-results-page .fcc-ai-progress {
        height: 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.06);
        overflow: hidden;
    }

    .fcc-results-page .fcc-ai-progress > span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #5eead4 0%, #38bdf8 100%);
        box-shadow: 0 0 16px rgba(56, 189, 248, 0.32);
    }

    .fcc-results-page .fcc-ai-progress-gold > span {
        background: linear-gradient(90deg, #f59e0b 0%, #fde047 100%);
        box-shadow: 0 0 16px rgba(245, 158, 11, 0.28);
    }

    .fcc-results-page .fcc-ai-stage-card {
        border: 1px solid rgba(127, 215, 208, 0.12);
        border-radius: 16px;
        background:
            radial-gradient(200px 90px at 100% 0%, rgba(56, 189, 248, 0.07), transparent 70%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.025));
        padding: 0.95rem 1rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
    }

    .fcc-results-page .fcc-inline-kv {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.8rem;
    }

    .fcc-results-page .fcc-ai-widget .text-muted,
    .fcc-results-page .fcc-ai-stage-card .text-muted {
        color: rgba(214, 241, 239, 0.78) !important;
    }

    .fcc-results-page .fcc-ai-widget .text-white-50 {
        color: rgba(235, 250, 248, 0.84) !important;
    }

    .fcc-results-page .fcc-ai-widget strong,
    .fcc-results-page .fcc-ai-widget .h6 {
        color: #f5fffd;
    }

    .fcc-results-page .fcc-ai-stage-card .fcc-inline-kv strong {
        font-size: 1.15rem;
        line-height: 1;
        color: #f7fffe;
        text-align: right;
    }

    .fcc-results-page .fcc-ai-widget .fcc-metric-note {
        color: #effffc;
        background: rgba(127, 215, 208, 0.11);
        border-color: rgba(127, 215, 208, 0.16);
    }

    .fcc-results-page .fcc-ai-headline {
        font-size: 1.35rem;
        line-height: 1.15;
        letter-spacing: -0.01em;
    }

    .fcc-results-page .fcc-ai-subcopy {
        max-width: 28rem;
        line-height: 1.5;
    }

    .fcc-results-page .fcc-ai-signal-value {
        font-size: 1.45rem;
        line-height: 1;
        font-weight: 800;
        color: #f4fffd;
    }

    .fcc-results-page .fcc-ai-value-stack {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.2rem;
        min-width: 82px;
    }

    .fcc-results-page .fcc-ai-value-label {
        font-size: 10px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(198, 236, 232, 0.72);
        font-weight: 700;
        line-height: 1;
    }

    .fcc-results-page .fcc-ai-kicker {
        font-size: 10px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(180, 226, 223, 0.72);
        margin-bottom: 0.35rem;
        font-weight: 700;
    }

    .fcc-results-page .fcc-ai-benefit {
        color: rgba(240, 255, 252, 0.88);
        line-height: 1.5;
    }

    .fcc-results-page .fcc-ai-meter-label {
        font-size: 12px;
        color: rgba(212, 243, 240, 0.78);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
    }

    .fcc-results-page .fcc-ai-footnote {
        font-size: 13px;
        line-height: 1.55;
    }
</style>

<div class="container">
    <div class="fcc-results-page">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
        <div>
            <h1 class="h3 mb-1 fcc-title"><?= l('fcc_results.header') ?></h1>
            <p class="text-muted mb-0"><?= l('fcc_results.subheader') ?></p>
        </div>
    </div>

    <?php $selected_period = $data->selected_period; ?>
    <?php $selected_period_data = $data->periods[$selected_period] ?? null; ?>

    <div class="card fcc-hero-card shadow-sm mb-3">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-start">
            <div class="flex-grow-1 mb-3 mb-lg-0">
                <div class="small text-muted mb-1"><?= l('fcc_results.qualified_list_title') ?></div>
                <div class="fcc-hero-top">
                    <div class="h6 fcc-qualification-text"><?= sprintf(l('fcc_results.qualification_note'), nr($data->min_qualified_clicks)) ?></div>

                    <div class="fcc-period-panel">
                        <div class="btn-group fcc-segmented" role="group" aria-label="FCC results period">
                            <a href="<?= url('fcc-results?period=30d') ?>" class="btn btn-sm <?= $selected_period === '30d' ? 'btn-primary' : 'btn-outline-primary' ?>"><?= l('fcc_results.period_30d') ?></a>
                            <a href="<?= url('fcc-results?period=7d') ?>" class="btn btn-sm <?= $selected_period === '7d' ? 'btn-primary' : 'btn-outline-primary' ?>"><?= l('fcc_results.period_7d') ?></a>
                        </div>
                    </div>
                </div>

                <div class="small fcc-hero-note">
                    <strong class="d-block mb-1"><?= l('fcc_results.homepage_spotlight_title') ?></strong>
                    <?= l('fcc_results.homepage_spotlight_notice_before') ?>
                    <a href="<?= url('featured-apps') ?>"><?= l('featured_apps.title') ?></a>
                    <?= l('fcc_results.homepage_spotlight_notice_after') ?>
                </div>

                <div class="small fcc-metric-note mt-3 mb-0"><?= l('fcc_results.proof_note') ?></div>
            </div>
        </div>
    </div>

    <div class="row m-n2">
        <div class="col-12 col-xl-8 p-2">
            <div class="card fcc-card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h2 class="h6 mb-0"><?= l('fcc_results.qualified_list_title') ?> • <?= $selected_period === '7d' ? l('fcc_results.period_7d') : l('fcc_results.period_30d') ?></h2>
                        <span class="badge badge-light"><?= nr($selected_period_data['qualified_total'] ?? 0) ?></span>
                    </div>
                    <p class="small text-muted mb-3"><?= l('fcc_results.qualified_list_subheader') ?></p>
                    <div class="small fcc-metric-note mb-3"><?= l('fcc_results.metrics_note') ?></div>

                    <?php if(empty($selected_period_data['leaderboard'])): ?>
                        <div class="alert alert-light mb-0"><?= l('fcc_results.qualified_list_empty') ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table fcc-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-muted" style="width: 70px;"><?= l('fcc_results.table.position') ?></th>
                                        <th class="text-muted"><?= l('fcc_results.table.user') ?></th>
                                        <th class="text-muted text-right"><?= l('fcc_results.table.shop_clicks') ?>
                                            <span data-toggle="tooltip" title="<?= l('fcc_results.metrics_info.shop_clicks') ?>"><i class="fas fa-info-circle fcc-help-icon"></i></span>
                                        </th>
                                        <th class="text-muted text-right"><?= l('fcc_results.table.ctr') ?>
                                            <span data-toggle="tooltip" title="<?= l('fcc_results.metrics_info.ctr') ?>"><i class="fas fa-info-circle fcc-help-icon"></i></span>
                                        </th>
                                        <th class="text-muted text-right"><?= l('fcc_results.table.trend') ?>
                                            <span data-toggle="tooltip" title="<?= l('fcc_results.metrics_info.trend') ?>"><i class="fas fa-info-circle fcc-help-icon"></i></span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($selected_period_data['leaderboard'] as $entry): ?>
                                        <?php $is_current_user = (int) $entry['user_id'] === (int) $this->user->user_id; ?>
                                        <tr class="<?= $is_current_user ? 'table-active' : null ?>">
                                            <td class="font-weight-bold"><?= nr($entry['rank']) ?></td>
                                            <td>
                                                <div class="d-flex flex-wrap align-items-center">
                                                    <span class="mr-2"><?= $entry['name'] ?></span>
                                                    <?php if($entry['is_top_three']): ?>
                                                        <span class="fcc-chip fcc-chip-top"><?= l('fcc_results.badge.top3') ?></span>
                                                    <?php endif ?>
                                                    <?php if($entry['is_rising']): ?>
                                                        <span class="fcc-chip fcc-chip-rising"><?= l('fcc_results.badge.rising') ?></span>
                                                    <?php endif ?>
                                                    <?php if($entry['is_falling']): ?>
                                                        <span class="fcc-chip fcc-chip-falling"><?= l('fcc_results.badge.falling') ?></span>
                                                    <?php endif ?>
                                                </div>
                                            </td>
                                            <td class="text-right font-weight-bold"><?= nr($entry['qualified_clicks']) ?></td>
                                            <td class="text-right">
                                                <?= $entry['ctr'] === null ? '<span class="text-muted small">' . l('fcc_results.pending_rate') . '</span>' : nr($entry['ctr']) . '%' ?>
                                            </td>
                                            <td class="text-right <?= $entry['trend_percent'] > 0 ? 'text-success' : ($entry['trend_percent'] < 0 ? 'text-danger' : 'text-muted') ?>">
                                                <?php $trend_sign = $entry['trend_percent'] > 0 ? '+' : ''; ?>
                                                <?= $trend_sign . nr($entry['trend_percent']) ?>%
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4 p-2">
            <div class="card fcc-card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3"><?= l('fcc_results.you.title') ?></h2>

                    <?php $current_user_data = $selected_period_data['current_user']; ?>
                    <?php if($current_user_data['is_qualified']): ?>
                        <div class="alert alert-success py-2 small"><?= l('fcc_results.you.qualified') ?></div>
                    <?php else: ?>
                        <div class="alert alert-light border py-2 small"><?= l('fcc_results.you.not_qualified') ?></div>
                    <?php endif ?>

                    <div class="small">
                        <div class="fcc-stat-row">
                            <span>
                                <?= l('fcc_results.you.rank') ?>
                                <span data-toggle="tooltip" title="<?= l('fcc_results.metrics_info.position') ?>"><i class="fas fa-info-circle fcc-help-icon"></i></span>
                            </span>
                            <strong><?= $data->current_user_rank ? nr($data->current_user_rank) : '-' ?></strong>
                        </div>
                        <div class="fcc-stat-row">
                            <span>
                                <?= l('fcc_results.you.shop_clicks') ?>
                                <span data-toggle="tooltip" title="<?= l('fcc_results.metrics_info.shop_clicks') ?>"><i class="fas fa-info-circle fcc-help-icon"></i></span>
                            </span>
                            <strong><?= nr($current_user_data['qualified_clicks']) ?></strong>
                        </div>
                        <div class="fcc-stat-row">
                            <span><?= l('fcc_results.you.app_clicks') ?></span>
                            <strong><?= nr($current_user_data['app_clicks']) ?></strong>
                        </div>
                        <div class="fcc-stat-row">
                            <span><?= l('fcc_results.you.blog_clicks') ?></span>
                            <strong><?= nr($current_user_data['blog_clicks']) ?></strong>
                        </div>
                        <div class="fcc-stat-row">
                            <span>
                                <?= l('fcc_results.you.funnel_contacts') ?>
                                <span data-toggle="tooltip" title="<?= l('fcc_results.metrics_info.total_contacts') ?>"><i class="fas fa-info-circle fcc-help-icon"></i></span>
                            </span>
                            <strong><?= nr($current_user_data['total_contacts'] ?? 0) ?></strong>
                        </div>
                        <div class="fcc-stat-breakdown">
                            <?= sprintf(
                                l('fcc_results.you.contacts_breakdown'),
                                nr($current_user_data['funnel_contacts'] ?? 0),
                                nr($current_user_data['ai_chat_contacts'] ?? 0)
                            ) ?>
                        </div>
                        <div class="fcc-stat-row">
                            <span><?= l('fcc_results.you.ai_chat_contacts') ?></span>
                            <strong><?= nr($current_user_data['ai_chat_contacts'] ?? 0) ?></strong>
                        </div>
                        <div class="fcc-stat-row">
                            <span>
                                <?= l('fcc_results.you.ctr') ?>
                                <span data-toggle="tooltip" title="<?= l('fcc_results.metrics_info.ctr') ?>"><i class="fas fa-info-circle fcc-help-icon"></i></span>
                            </span>
                            <strong><?= $current_user_data['ctr'] === null ? l('fcc_results.pending_rate') : nr($current_user_data['ctr']) . '%' ?></strong>
                        </div>

                        <?php if(!$current_user_data['is_qualified']): ?>
                            <div class="fcc-stat-row">
                                <span>
                                    <?= l('fcc_results.you.to_qualification') ?>
                                    <span data-toggle="tooltip" title="<?= l('fcc_results.metrics_info.to_qualification') ?>"><i class="fas fa-info-circle fcc-help-icon"></i></span>
                                </span>
                                <strong><?= nr($data->distance_to_qualification) ?></strong>
                            </div>
                        <?php else: ?>
                            <?php if($data->distance_to_next_rank !== null): ?>
                                <div class="fcc-stat-row">
                                    <span>
                                        <?= l('fcc_results.you.to_next_rank') ?>
                                        <span data-toggle="tooltip" title="<?= l('fcc_results.metrics_info.to_next_rank') ?>"><i class="fas fa-info-circle fcc-help-icon"></i></span>
                                    </span>
                                    <strong><?= nr($data->distance_to_next_rank) ?></strong>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light border py-2 px-3 mb-0 small"><?= l('fcc_results.you.first_place') ?></div>
                            <?php endif ?>
                        <?php endif ?>
                    </div>
                </div>
            </div>

            <?php $ai_unlock = $data->ai_unlock; ?>
            <div class="card fcc-card fcc-ai-widget shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <div class="fcc-ai-kicker"><?= l('ai_plan.title') ?></div>
                            <h2 class="fcc-ai-headline mb-2"><?= l('fcc_results.ai_widget.title') ?></h2>
                            <p class="small text-muted mb-0 fcc-ai-subcopy"><?= l('fcc_results.ai_widget.subheader') ?></p>
                        </div>
                        <span class="fcc-ai-pill <?= $ai_unlock['stage'] === 'vip' ? 'fcc-ai-pill-vip' : ($ai_unlock['stage'] === 'active' ? 'fcc-ai-pill-active' : 'fcc-ai-pill-starter') ?>">
                            <?= l('fcc_results.ai_widget.stage_' . $ai_unlock['stage']) ?>
                        </span>
                    </div>

                    <div class="fcc-ai-stage-card mb-3">
                        <div class="fcc-inline-kv mb-3">
                            <div>
                                <div class="fcc-ai-meter-label mb-1"><?= l('fcc_results.ai_widget.signal_30d') ?></div>
                                <div class="small text-muted"><?= l('fcc_results.ai_widget.signal_breakdown') ?></div>
                            </div>
                            <div class="fcc-ai-value-stack">
                                <span class="fcc-ai-value-label"><?= l('fcc_results.ai_widget.total_label') ?></span>
                                <div class="fcc-ai-signal-value"><?= nr($ai_unlock['signal_30d']) ?></div>
                            </div>
                        </div>
                        <div class="small text-white-50 fcc-ai-benefit">
                            <?= sprintf(l('fcc_results.ai_widget.signal_breakdown_text'), nr($ai_unlock['app_clicks_30d']), nr($ai_unlock['blog_clicks_30d'])) ?>
                        </div>
                    </div>

                    <div class="fcc-ai-stage-card mb-3">
                        <div class="fcc-inline-kv mb-3">
                            <span class="fcc-ai-meter-label"><?= sprintf(l('fcc_results.ai_widget.active_goal'), nr($ai_unlock['active_threshold'])) ?></span>
                            <div class="fcc-ai-value-stack">
                                <span class="fcc-ai-value-label"><?= $ai_unlock['to_active'] > 0 ? l('fcc_results.ai_widget.remaining_label') : l('fcc_results.ai_widget.status_label') ?></span>
                                <strong><?= $ai_unlock['to_active'] > 0 ? nr($ai_unlock['to_active']) : l('fcc_results.ai_widget.unlocked_short') ?></strong>
                            </div>
                        </div>
                        <div class="fcc-ai-progress mb-3"><span style="width: <?= (int) $ai_unlock['active_progress_percent'] ?>%"></span></div>
                        <div class="small text-white-50 fcc-ai-benefit"><?= l('fcc_results.ai_widget.active_benefits') ?></div>
                    </div>

                    <div class="fcc-ai-stage-card mb-3">
                        <div class="fcc-inline-kv mb-3">
                            <span class="fcc-ai-meter-label"><?= sprintf(l('fcc_results.ai_widget.vip_goal'), nr($ai_unlock['vip_threshold'])) ?></span>
                            <div class="fcc-ai-value-stack">
                                <span class="fcc-ai-value-label"><?= $ai_unlock['to_vip'] > 0 ? l('fcc_results.ai_widget.remaining_label') : l('fcc_results.ai_widget.status_label') ?></span>
                                <strong><?= $ai_unlock['to_vip'] > 0 ? nr($ai_unlock['to_vip']) : l('fcc_results.ai_widget.unlocked_short') ?></strong>
                            </div>
                        </div>
                        <div class="fcc-ai-progress fcc-ai-progress-gold mb-3"><span style="width: <?= (int) $ai_unlock['vip_progress_percent'] ?>%"></span></div>
                        <div class="small text-white-50 fcc-ai-benefit"><?= l('fcc_results.ai_widget.vip_benefits') ?></div>
                    </div>

                    <div class="small fcc-metric-note mb-0 fcc-ai-footnote">
                        <?= $ai_unlock['is_pro'] ? l('fcc_results.ai_widget.pro_note') : l('fcc_results.ai_widget.pro_required_note') ?>
                    </div>
                </div>
            </div>

            <div class="card fcc-card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-2"><?= l('fcc_results.challenge.title') ?></h2>
                    <p class="small text-muted mb-0"><?= sprintf(l('fcc_results.challenge.text'), nr($data->min_qualified_clicks)) ?></p>
                </div>
            </div>

            <div class="card fcc-card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-2"><?= l('fcc_results.tips.title') ?></h2>
                    <p class="small text-muted"><?= l('fcc_results.tips.subheader') ?></p>

                    <ul class="small pl-3 mb-0">
                        <?php foreach($data->tips as $tip): ?>
                            <li class="mb-2"><?= $tip ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-14 -->
