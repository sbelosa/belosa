<?php defined('ALTUMCODE') || die() ?>

<?php $feature_is_available = $data->feature_is_available ?? true; ?>

<!-- Custom code: FC-2026-03-31: User AI plan phase 1-3 view -->
<style>
    .ai-plan-shell .ai-plan-card { border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 1rem; background: linear-gradient(155deg, rgba(255,255,255,.03), rgba(255,255,255,.01)); box-shadow: 0 1rem 2rem rgba(15,23,42,.12); }
    .ai-plan-shell .ai-plan-hero { background: radial-gradient(900px 260px at -10% -70%, rgba(14,165,233,.18), transparent 65%), linear-gradient(155deg, rgba(255,255,255,.03), rgba(255,255,255,.01)); }
    .ai-plan-shell .ai-plan-chip { display:inline-flex; align-items:center; padding:.35rem .65rem; border-radius:999px; font-size:.74rem; font-weight:700; letter-spacing:.03em; text-transform:uppercase; border:1px solid rgba(148,163,184,.16); background:rgba(15,23,42,.06); }
    .ai-plan-shell .ai-plan-chip.active { color:#075985; background:rgba(125,211,252,.22); border-color:rgba(14,165,233,.24); }
    .ai-plan-shell .ai-plan-chip.locked { color:#9a3412; background:rgba(253,186,116,.18); border-color:rgba(249,115,22,.2); }
    .ai-plan-shell .ai-plan-chip.success { color:#166534; background:rgba(134,239,172,.2); border-color:rgba(34,197,94,.18); }
    .ai-plan-shell .ai-plan-option-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:.75rem; }
    .ai-plan-shell .ai-plan-option input { display:none; }
    .ai-plan-shell .ai-plan-option label { width:100%; margin:0; padding:.8rem .9rem; border-radius:.85rem; border:1px solid rgba(148,163,184,.18); background:rgba(255,255,255,.02); font-weight:600; cursor:pointer; transition:border-color .2s ease, background .2s ease, transform .2s ease; }
    .ai-plan-shell .ai-plan-option input:checked + label { border-color:rgba(14,165,233,.38); background:rgba(125,211,252,.12); transform:translateY(-1px); }
    .ai-plan-shell .ai-plan-phase-list { display:grid; gap:.75rem; }
    .ai-plan-shell .ai-plan-phase-item { padding:.9rem 1rem; border-radius:.9rem; border:1px solid rgba(148,163,184,.12); background:rgba(255,255,255,.02); }
    .ai-plan-shell .ai-plan-phase-item.active { border-color:rgba(14,165,233,.25); background:rgba(125,211,252,.08); }
    .ai-plan-shell .ai-plan-stat-row { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:.7rem 0; border-bottom:1px solid rgba(148,163,184,.12); }
    .ai-plan-shell .ai-plan-stat-row:last-child { border-bottom:0; padding-bottom:0; }
    .ai-plan-shell .ai-plan-lock-box { border: 1px dashed rgba(148,163,184,.25); border-radius: .9rem; padding: 1rem; background: rgba(15,23,42,.02); }
    .ai-plan-shell .ai-plan-history-item { padding: .85rem 0; border-bottom: 1px solid rgba(148,163,184,.12); }
    .ai-plan-shell .ai-plan-history-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .ai-plan-shell .ai-plan-outcome-box { border: 1px dashed rgba(14,165,233,.18); border-radius: .95rem; padding: 1rem; background: rgba(14,165,233,.03); }
    .ai-plan-shell .ai-plan-day-card { border: 1px solid rgba(148,163,184,.12); border-radius: .9rem; padding: 1rem; background: rgba(255,255,255,.02); height: 100%; }
    .ai-plan-shell .ai-plan-pill { display:inline-flex; align-items:center; padding:.25rem .6rem; border-radius:999px; font-size:.72rem; font-weight:600; background:rgba(125,211,252,.12); color:#075985; border:1px solid rgba(14,165,233,.14); }
    .ai-plan-shell .ai-plan-list { margin:0; padding-left:1.1rem; }
    .ai-plan-shell .ai-plan-plan-block { border-top:1px solid rgba(148,163,184,.1); padding-top:1rem; }
    .ai-plan-shell .ai-plan-day-stream { position:relative; display:grid; gap:1rem; }
    .ai-plan-shell .ai-plan-day-stream::before { content:''; position:absolute; top:.6rem; bottom:.6rem; left:111px; width:2px; background:linear-gradient(180deg, rgba(14,165,233,.14), rgba(125,211,252,.55), rgba(14,165,233,.14)); }
    .ai-plan-shell .ai-plan-day-row { position:relative; display:grid; grid-template-columns:88px 30px minmax(0, 1fr); gap:1rem; align-items:stretch; }
    .ai-plan-shell .ai-plan-day-side { display:flex; flex-direction:column; justify-content:flex-start; align-items:flex-end; gap:.6rem; min-width:0; padding-top:.35rem; text-align:right; }
    .ai-plan-shell .ai-plan-day-marker { position:relative; display:flex; align-items:flex-start; justify-content:center; padding-top:.45rem; }
    .ai-plan-shell .ai-plan-day-node { position:relative; z-index:1; width:14px; height:14px; border-radius:999px; background:linear-gradient(180deg, #38bdf8, #0ea5e9); box-shadow:0 0 0 6px rgba(14,165,233,.12), 0 8px 18px rgba(14,165,233,.22); }
    .ai-plan-shell .ai-plan-day-card { border:1px solid rgba(148,163,184,.12); border-radius:1.1rem; padding:1rem 1.05rem; background:linear-gradient(135deg, rgba(255,255,255,.03), rgba(255,255,255,.015)); box-shadow:0 .75rem 1.5rem rgba(15,23,42,.08); }
    .ai-plan-shell .ai-plan-day-badge { display:inline-flex; align-items:center; align-self:flex-start; padding:.28rem .62rem; border-radius:999px; font-size:.72rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#0369a1; background:rgba(125,211,252,.18); border:1px solid rgba(14,165,233,.18); }
    .ai-plan-shell .ai-plan-day-title { font-size:1.45rem; font-weight:800; line-height:1.08; letter-spacing:-.02em; margin:0; max-width:12ch; color:#f8fafc; }
    .ai-plan-shell .ai-plan-day-content { min-width:0; display:flex; align-items:center; }
    .ai-plan-shell .ai-plan-task-list { list-style:none; margin:0; padding:0; width:100%; display:grid; gap:.8rem; }
    .ai-plan-shell .ai-plan-task-item { position:relative; padding-left:1.25rem; font-size:1rem; line-height:1.7; color:rgba(226,232,240,.96); }
    .ai-plan-shell .ai-plan-task-item::before { content:''; position:absolute; top:.72rem; left:0; width:.42rem; height:.42rem; border-radius:999px; background:#0ea5e9; box-shadow:0 0 0 4px rgba(14,165,233,.12); }
    .ai-plan-shell .ai-plan-advice-stack { display:grid; gap:1rem; margin-top:1rem; }
    .ai-plan-shell .ai-plan-advice-row { display:grid; grid-template-columns:260px minmax(0, 1fr); gap:1.1rem; border:1px solid rgba(148,163,184,.12); border-radius:1.1rem; padding:1rem; background:linear-gradient(135deg, rgba(255,255,255,.03), rgba(255,255,255,.015)); box-shadow:0 .75rem 1.5rem rgba(15,23,42,.07); }
    .ai-plan-shell .ai-plan-advice-side { display:flex; flex-direction:column; justify-content:space-between; gap:.75rem; padding:.2rem 0; }
    .ai-plan-shell .ai-plan-advice-kicker { font-size:.72rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:rgba(2,132,199,.9); }
    .ai-plan-shell .ai-plan-advice-title { font-size:1.28rem; font-weight:800; line-height:1.15; letter-spacing:-.02em; margin-bottom:.45rem; color:#f8fafc; }
    .ai-plan-shell .ai-plan-advice-note { font-size:.94rem; line-height:1.55; color:rgba(148,163,184,.95); max-width:22ch; }
    .ai-plan-shell .ai-plan-advice-copy { font-size:.98rem; line-height:1.68; display:flex; align-items:center; }
    .ai-plan-shell .ai-plan-advice-row.is-content .ai-plan-advice-kicker { color:#7c3aed; }
    .ai-plan-shell .ai-plan-advice-row.is-coach .ai-plan-advice-kicker { color:#0891b2; }
    .ai-plan-shell .ai-plan-advice-row.is-boundary .ai-plan-advice-kicker { color:#b45309; }
    .ai-plan-shell .ai-plan-review-grid { display:grid; grid-template-columns:1.2fr .95fr; gap:1rem; }
    .ai-plan-shell .ai-plan-review-box { border:1px solid rgba(148,163,184,.12); border-radius:1rem; padding:1rem; background:rgba(255,255,255,.02); height:100%; }
    .ai-plan-shell .ai-plan-review-list { list-style:none; margin:0; padding:0; display:grid; gap:.75rem; }
    .ai-plan-shell .ai-plan-review-list li { position:relative; padding-left:1rem; color:rgba(226,232,240,.96); }
    .ai-plan-shell .ai-plan-review-list li::before { content:''; position:absolute; top:.6rem; left:0; width:.38rem; height:.38rem; border-radius:999px; background:#0ea5e9; }
    .ai-plan-shell .ai-plan-review-order { display:grid; gap:.65rem; }
    .ai-plan-shell .ai-plan-review-order-item { display:flex; gap:.85rem; align-items:flex-start; border-bottom:1px solid rgba(148,163,184,.08); padding-bottom:.65rem; }
    .ai-plan-shell .ai-plan-review-order-item:last-child { border-bottom:0; padding-bottom:0; }
    .ai-plan-shell .ai-plan-review-order-step { width:1.6rem; height:1.6rem; flex:0 0 1.6rem; display:inline-flex; align-items:center; justify-content:center; border-radius:999px; font-size:.78rem; font-weight:700; background:rgba(125,211,252,.16); color:#0369a1; }
    .ai-plan-shell .ai-plan-inline-meta { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem; }
    .ai-plan-shell .ai-plan-soft-box { border:1px solid rgba(148,163,184,.12); border-radius:.9rem; padding:.85rem 1rem; background:rgba(255,255,255,.02); }
    .ai-plan-shell .ai-plan-preview-card { border:1px solid rgba(148,163,184,.12); border-radius:1rem; background:linear-gradient(160deg, rgba(255,255,255,.025), rgba(255,255,255,.01)); overflow:hidden; min-height:100%; display:flex; flex-direction:column; }
    .ai-plan-shell .ai-plan-preview-header { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; padding:1rem 1rem .85rem; border-bottom:1px solid rgba(148,163,184,.1); }
    .ai-plan-shell .ai-plan-preview-meta { font-size:.84rem; color:rgba(148,163,184,.92); }
    .ai-plan-shell .ai-plan-preview-frame-wrap { position:relative; min-height:560px; background:rgba(2,6,23,.48); }
    .ai-plan-shell .ai-plan-preview-frame { width:100%; min-height:560px; border:0; background:#fff; display:block; }
    .ai-plan-shell .ai-plan-preview-empty { min-height:560px; display:flex; align-items:center; justify-content:center; padding:1.5rem; text-align:center; color:rgba(148,163,184,.92); }
    .ai-plan-shell .ai-plan-review-highlight { border:1px solid rgba(45,212,191,.16); border-radius:1rem; padding:1.05rem 1.1rem; background:linear-gradient(135deg, rgba(45,212,191,.08), rgba(14,165,233,.04)); }
    .ai-plan-shell .ai-plan-review-highlight-grid { display:grid; grid-template-columns:.9fr 1.2fr .9fr; gap:1rem; }
    .ai-plan-shell .ai-plan-review-highlight-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:rgba(148,163,184,.84); margin-bottom:.35rem; }
    .ai-plan-shell .ai-plan-review-highlight-copy { color:rgba(226,232,240,.96); line-height:1.65; }
    .ai-plan-shell .ai-plan-review-disclosure-stack { display:grid; gap:.9rem; }
    .ai-plan-shell .ai-plan-review-disclosure { border:1px solid rgba(148,163,184,.1); border-radius:1rem; background:rgba(255,255,255,.018); overflow:hidden; }
    .ai-plan-shell .ai-plan-review-disclosure summary { list-style:none; cursor:pointer; display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.05rem; font-weight:700; color:#f8fafc; }
    .ai-plan-shell .ai-plan-review-disclosure summary::-webkit-details-marker { display:none; }
    .ai-plan-shell .ai-plan-review-disclosure summary::after { content:'+'; font-size:1.1rem; color:#2dd4bf; }
    .ai-plan-shell .ai-plan-review-disclosure[open] summary::after { content:'-'; }
    .ai-plan-shell .ai-plan-review-disclosure-body { padding:0 1.05rem 1rem; border-top:1px solid rgba(148,163,184,.08); }
    .ai-plan-shell .ai-plan-review-disclosure-note { color:rgba(148,163,184,.9); font-size:.86rem; }
    .ai-plan-shell .ai-plan-section-nav { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:.85rem; }
    .ai-plan-shell .ai-plan-section-link { display:flex; flex-direction:column; gap:.45rem; padding:1rem 1.05rem; border:1px solid rgba(148,163,184,.12); border-radius:1rem; background:linear-gradient(145deg, rgba(255,255,255,.025), rgba(255,255,255,.012)); color:inherit; text-decoration:none; transition:transform .2s ease, border-color .2s ease, box-shadow .2s ease, background .2s ease; min-height:100%; }
    .ai-plan-shell .ai-plan-section-link:hover { color:inherit; text-decoration:none; transform:translateY(-1px); border-color:rgba(14,165,233,.22); box-shadow:0 .8rem 1.5rem rgba(15,23,42,.08); }
    .ai-plan-shell .ai-plan-section-link.active { border-color:rgba(45,212,191,.22); background:linear-gradient(145deg, rgba(45,212,191,.1), rgba(14,165,233,.05)); box-shadow:0 1rem 1.8rem rgba(15,23,42,.1); }
    .ai-plan-shell .ai-plan-section-kicker { display:inline-flex; align-items:center; align-self:flex-start; padding:.22rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#99f6e4; background:rgba(45,212,191,.12); border:1px solid rgba(45,212,191,.14); }
    .ai-plan-shell .ai-plan-overview-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:1rem; }
    .ai-plan-shell .ai-plan-overview-card { display:flex; flex-direction:column; gap:.75rem; padding:1.1rem; border:1px solid rgba(148,163,184,.12); border-radius:1rem; background:linear-gradient(145deg, rgba(255,255,255,.03), rgba(255,255,255,.015)); color:inherit; text-decoration:none; }
    .ai-plan-shell .ai-plan-overview-card:hover { color:inherit; text-decoration:none; border-color:rgba(14,165,233,.2); }
    .ai-plan-shell .ai-plan-overview-step { font-size:.72rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:rgba(125,211,252,.88); }
    .ai-plan-shell .ai-plan-overview-copy { color:rgba(148,163,184,.92); font-size:.92rem; line-height:1.55; }
    .ai-plan-shell .ai-plan-guide-card { border:1px solid rgba(45,212,191,.16); background:linear-gradient(135deg, rgba(45,212,191,.08), rgba(14,165,233,.04)); }
    .ai-plan-shell .ai-plan-step-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:1rem; }
    .ai-plan-shell .ai-plan-step-card { display:flex; flex-direction:column; gap:.65rem; padding:1rem 1.05rem; border:1px solid rgba(148,163,184,.12); border-radius:1rem; background:linear-gradient(145deg, rgba(255,255,255,.025), rgba(255,255,255,.012)); color:inherit; text-decoration:none; min-height:100%; }
    .ai-plan-shell .ai-plan-step-card:hover { color:inherit; text-decoration:none; border-color:rgba(14,165,233,.2); }
    .ai-plan-shell .ai-plan-step-card.current { border-color:rgba(45,212,191,.24); background:linear-gradient(145deg, rgba(45,212,191,.1), rgba(14,165,233,.05)); box-shadow:0 1rem 1.8rem rgba(15,23,42,.1); }
    .ai-plan-shell .ai-plan-step-card.done { border-color:rgba(34,197,94,.18); }
    .ai-plan-shell .ai-plan-step-card.locked { border-color:rgba(249,115,22,.18); }
    .ai-plan-shell .ai-plan-step-status { display:inline-flex; align-items:center; align-self:flex-start; padding:.22rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; border:1px solid rgba(148,163,184,.16); }
    .ai-plan-shell .ai-plan-step-status.current { color:#0f766e; background:rgba(45,212,191,.14); border-color:rgba(45,212,191,.2); }
    .ai-plan-shell .ai-plan-step-status.done { color:#166534; background:rgba(134,239,172,.18); border-color:rgba(34,197,94,.18); }
    .ai-plan-shell .ai-plan-step-status.locked { color:#9a3412; background:rgba(253,186,116,.16); border-color:rgba(249,115,22,.18); }
    .ai-plan-shell .ai-plan-step-status.next { color:#075985; background:rgba(125,211,252,.18); border-color:rgba(14,165,233,.18); }
    .ai-plan-shell .ai-plan-main-column { display:grid; gap:1.5rem; }
    .ai-plan-shell .ai-plan-main-column > .card { margin-top:0 !important; }
    .ai-plan-shell .ai-plan-sticky-column { position:sticky; top:1.25rem; }
    @media (max-width: 991.98px) {
        .ai-plan-shell .ai-plan-day-row,
        .ai-plan-shell .ai-plan-advice-row,
        .ai-plan-shell .ai-plan-review-grid,
        .ai-plan-shell .ai-plan-section-nav,
        .ai-plan-shell .ai-plan-overview-grid,
        .ai-plan-shell .ai-plan-step-grid { grid-template-columns:1fr; gap:.75rem; }
        .ai-plan-shell .ai-plan-review-highlight-grid { grid-template-columns:1fr; }
        .ai-plan-shell .ai-plan-preview-frame-wrap,
        .ai-plan-shell .ai-plan-preview-frame,
        .ai-plan-shell .ai-plan-preview-empty { min-height:420px; }
        .ai-plan-shell .ai-plan-day-stream::before { display:none; }
        .ai-plan-shell .ai-plan-day-side { align-items:flex-start; text-align:left; padding-top:0; }
        .ai-plan-shell .ai-plan-day-marker { display:none; }
        .ai-plan-shell .ai-plan-day-title { max-width:none; font-size:1.2rem; }
        .ai-plan-shell .ai-plan-advice-note { max-width:none; }
        .ai-plan-shell .ai-plan-sticky-column { position:static; top:auto; }
    }
    @media (max-width: 767.98px) {
        .ai-plan-shell .ai-plan-option-grid { grid-template-columns:1fr; }
    }
</style>

<?php $values = $data->values; ?>
<?php $weekly_values = $data->weekly_values; ?>
<?php $app_review_selected_app = $data->app_review_selected_app ?? []; ?>
<?php $app_review_preview_label = (string) (($app_review_selected_app['name'] ?? '') ?: ($app_review_selected_app['url'] ?? ($data->latest_app_review['selected_app_name'] ?? '-'))); ?>
<?php $app_review_preview_url = (string) ($app_review_selected_app['public_url'] ?? ''); ?>
<?php if($app_review_preview_url === '' && !empty($app_review_selected_app['url'])) $app_review_preview_url = url((string) $app_review_selected_app['url']); ?>
<?php $ai_plan_sections = ['profile', 'weekly', 'plan']; ?>
<?php $ai_plan_recommended_section = 'profile'; ?>
<?php if($data->is_profile_complete) $ai_plan_recommended_section = !$data->latest_weekly_checkin ? 'weekly' : 'plan'; ?>
<?php $ai_plan_active_section = (string) ($_GET['section'] ?? ''); ?>
<?php if(!in_array($ai_plan_active_section, $ai_plan_sections, true)) $ai_plan_active_section = isset($_POST['save_profile']) || isset($_POST['generate_app_review']) ? 'profile' : (isset($_POST['save_weekly_checkin']) ? 'weekly' : ((isset($_POST['regenerate_ai_plan']) || isset($_POST['save_weekly_outcome'])) ? 'plan' : $ai_plan_recommended_section)); ?>
<?php $ai_plan_section_urls = [
    'profile' => url('ai-plan?section=profile'),
    'weekly' => url('ai-plan?section=weekly'),
    'plan' => url('ai-plan?section=plan'),
]; ?>
<?php $ai_plan_guide_text = l('ai_plan.guide_profile_text'); ?>
<?php if($ai_plan_recommended_section === 'weekly') $ai_plan_guide_text = $data->is_weekly_plan_eligible ? l('ai_plan.guide_weekly_text') : l('ai_plan.guide_weekly_locked_text'); ?>
<?php if($ai_plan_recommended_section === 'plan') $ai_plan_guide_text = $data->latest_weekly_plan ? l('ai_plan.guide_outcome_text') : l('ai_plan.guide_plan_text'); ?>
<?php $ai_plan_profile_status = !$data->is_profile_complete ? 'current' : 'done'; ?>
<?php $ai_plan_weekly_status = !$data->is_profile_complete ? 'locked' : (!$data->latest_weekly_checkin ? 'current' : 'done'); ?>
<?php $ai_plan_plan_status = !$data->latest_weekly_checkin ? 'locked' : 'current'; ?>
<?php if($data->latest_weekly_plan && $data->latest_weekly_outcome) $ai_plan_plan_status = 'done'; ?>
<?php if($data->latest_weekly_checkin && !$data->latest_weekly_plan) $ai_plan_plan_status = 'current'; ?>
<?php if($data->is_profile_complete && !$data->latest_weekly_checkin) $ai_plan_plan_status = 'locked'; ?>

<div class="container ai-plan-shell">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= l('ai_plan.header') ?></h1>
            <p class="text-muted mb-0"><?= l('ai_plan.subheader') ?></p>
        </div>
    </div>

    <?= \Altum\Alerts::output_alerts() ?>

    <?php /* Custom code: FC-2026-03-31: Temporary unavailable state for non-admin users */ ?>
    <?php if(!$feature_is_available): ?>
        <div class="alert alert-info mb-0">
            <div class="font-weight-bold mb-1"><?= l('ai_plan.unavailable_title') ?></div>
            <div class="small mb-0"><?= l('ai_plan.unavailable_notice') ?></div>
        </div>
        <?php return; ?>
    <?php endif ?>
    <?php /* /Custom code: FC-2026-03-31 */ ?>

    <div class="card ai-plan-card ai-plan-hero mb-4"><div class="card-body"><div class="row align-items-center"><div class="col-12 col-xl-8 mb-3 mb-xl-0"><div class="d-flex flex-wrap align-items-center mb-2" style="gap:.5rem;"><span class="ai-plan-chip active"><?= l('ai_plan.phase_label') ?> 3</span><span class="ai-plan-chip <?= $data->is_weekly_plan_eligible ? 'active' : 'locked' ?>"><?= $data->is_weekly_plan_eligible ? l('ai_plan.status_ready') : l('ai_plan.status_building') ?></span><?php if($data->latest_weekly_plan): ?><span class="ai-plan-chip success"><?= l('ai_plan.status_plan_ready') ?></span><?php elseif($data->weekly_is_locked): ?><span class="ai-plan-chip locked"><?= l('ai_plan.weekly_status_cooldown') ?></span><?php elseif($data->latest_weekly_checkin): ?><span class="ai-plan-chip success"><?= l('ai_plan.weekly_status_submitted') ?></span><?php endif ?></div><h2 class="h4 mb-2"><?= l('ai_plan.hero_title_phase_3') ?></h2><p class="text-muted mb-0"><?= l('ai_plan.hero_text_phase_3') ?></p></div><div class="col-12 col-xl-4"><div class="ai-plan-card p-3 h-100"><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.metric_clicks') ?></span><strong><?= nr($data->current_clicks_30d) ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.metric_unlock') ?></span><strong><?= $data->is_weekly_plan_eligible ? l('ai_plan.metric_unlock_ready_phase_3') : l('ai_plan.metric_unlock_waiting') ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.weekly_next') ?></span><strong><?= $data->weekly_next_checkin_at ? \Altum\Date::get($data->weekly_next_checkin_at, 2) : l('ai_plan.weekly_now') ?></strong></div><div class="small text-muted mt-3 mb-0"><?= l('ai_plan.metric_help_phase_3') ?></div></div></div></div></div></div>

    <div class="card ai-plan-card ai-plan-guide-card mb-4"><div class="card-body"><div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:.75rem;"><div><h2 class="h5 mb-1"><?= l('ai_plan.guide_title') ?></h2><p class="text-muted mb-0"><?= $ai_plan_guide_text ?></p></div><a href="<?= $ai_plan_section_urls[$ai_plan_recommended_section] ?>" class="btn btn-primary"><?= l('ai_plan.section_' . $ai_plan_recommended_section) ?></a></div><div class="ai-plan-step-grid"><a href="<?= $ai_plan_section_urls['profile'] ?>" class="ai-plan-step-card <?= $ai_plan_profile_status ?>"><span class="ai-plan-step-status <?= $ai_plan_profile_status ?>"><?= l('ai_plan.step_status_' . $ai_plan_profile_status) ?></span><div class="font-weight-bold">1. <?= l('ai_plan.section_profile') ?></div><div class="text-muted small"><?= l('ai_plan.step_profile_text') ?></div></a><a href="<?= $ai_plan_section_urls['weekly'] ?>" class="ai-plan-step-card <?= $ai_plan_weekly_status ?>"><span class="ai-plan-step-status <?= $ai_plan_weekly_status ?>"><?= l('ai_plan.step_status_' . $ai_plan_weekly_status) ?></span><div class="font-weight-bold">2. <?= l('ai_plan.section_weekly') ?></div><div class="text-muted small"><?= l('ai_plan.step_weekly_text') ?></div></a><a href="<?= $ai_plan_section_urls['plan'] ?>" class="ai-plan-step-card <?= $ai_plan_plan_status ?>"><span class="ai-plan-step-status <?= $ai_plan_plan_status ?>"><?= l('ai_plan.step_status_' . $ai_plan_plan_status) ?></span><div class="font-weight-bold">3. <?= l('ai_plan.section_plan') ?></div><div class="text-muted small"><?= l('ai_plan.step_plan_text') ?></div></a></div></div></div>

    <div class="row">
        <div class="col-12 col-xl-8 mb-4 ai-plan-main-column">
            <?php if($ai_plan_active_section === 'profile'): ?>
            <div class="card ai-plan-card"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;"><div><h2 class="h5 mb-1"><?= l('ai_plan.form_title') ?></h2><p class="text-muted mb-0"><?= l('ai_plan.form_text') ?></p></div><?php if(!empty($values['updated_at'])): ?><div class="small text-muted"><?= l('ai_plan.last_updated') ?>: <?= \Altum\Date::get($values['updated_at'], 2) ?></div><?php endif ?></div>
                <form action="<?= $ai_plan_section_urls['profile'] ?>" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                    <div class="form-group"><label class="font-weight-bold d-block"><?= l('ai_plan.primary_goal') ?></label><div class="ai-plan-option-grid"><?php foreach($data->options['primary_goal'] as $option): ?><div class="ai-plan-option"><input type="radio" id="primary_goal_<?= $option ?>" name="primary_goal" value="<?= $option ?>" <?= $values['primary_goal'] === $option ? 'checked="checked"' : null ?> /><label for="primary_goal_<?= $option ?>"><?= l('ai_plan.option.primary_goal.' . $option) ?></label></div><?php endforeach ?></div><?= \Altum\Alerts::output_field_error('primary_goal') ?></div>

                    <div class="form-group"><label class="font-weight-bold d-block"><?= l('ai_plan.priority_offer') ?></label><div class="ai-plan-option-grid"><?php foreach($data->options['priority_offer'] as $option): ?><div class="ai-plan-option"><input type="radio" id="priority_offer_<?= $option ?>" name="priority_offer" value="<?= $option ?>" <?= $values['priority_offer'] === $option ? 'checked="checked"' : null ?> /><label for="priority_offer_<?= $option ?>"><?= l('ai_plan.option.priority_offer.' . $option) ?></label></div><?php endforeach ?></div><?= \Altum\Alerts::output_field_error('priority_offer') ?></div>

                    <div class="form-group"><label class="font-weight-bold d-block"><?= l('ai_plan.active_channels') ?></label><div class="ai-plan-option-grid"><?php foreach($data->options['active_channels'] as $option): ?><div class="ai-plan-option"><input type="checkbox" id="active_channels_<?= $option ?>" name="active_channels[]" value="<?= $option ?>" <?= in_array($option, $values['active_channels'], true) ? 'checked="checked"' : null ?> /><label for="active_channels_<?= $option ?>"><?= l('ai_plan.option.active_channels.' . $option) ?></label></div><?php endforeach ?></div><?= \Altum\Alerts::output_field_error('active_channels') ?></div>

                    <div class="form-row"><div class="col-12 col-md-6"><div class="form-group"><label for="available_time" class="font-weight-bold"><?= l('ai_plan.available_time') ?></label><select id="available_time" name="available_time" class="custom-select <?= \Altum\Alerts::has_field_errors('available_time') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->options['available_time'] as $option): ?><option value="<?= $option ?>" <?= $values['available_time'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.available_time.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('available_time') ?></div></div><div class="col-12 col-md-6"><div class="form-group"><label for="biggest_blocker" class="font-weight-bold"><?= l('ai_plan.biggest_blocker') ?></label><select id="biggest_blocker" name="biggest_blocker" class="custom-select <?= \Altum\Alerts::has_field_errors('biggest_blocker') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->options['biggest_blocker'] as $option): ?><option value="<?= $option ?>" <?= $values['biggest_blocker'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.biggest_blocker.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('biggest_blocker') ?></div></div></div>

                    <div class="form-row"><div class="col-12 col-md-6"><div class="form-group"><label for="communication_style" class="font-weight-bold"><?= l('ai_plan.communication_style') ?></label><select id="communication_style" name="communication_style" class="custom-select <?= \Altum\Alerts::has_field_errors('communication_style') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->options['communication_style'] as $option): ?><option value="<?= $option ?>" <?= $values['communication_style'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.communication_style.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('communication_style') ?></div></div><div class="col-12 col-md-6"><div class="form-group"><label for="follow_up_readiness" class="font-weight-bold"><?= l('ai_plan.follow_up_readiness') ?></label><select id="follow_up_readiness" name="follow_up_readiness" class="custom-select <?= \Altum\Alerts::has_field_errors('follow_up_readiness') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->options['follow_up_readiness'] as $option): ?><option value="<?= $option ?>" <?= $values['follow_up_readiness'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.follow_up_readiness.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('follow_up_readiness') ?></div></div></div>

                    <div class="form-group"><label for="weekly_change" class="font-weight-bold"><?= l('ai_plan.weekly_change') ?></label><select id="weekly_change" name="weekly_change" class="custom-select <?= \Altum\Alerts::has_field_errors('weekly_change') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->options['weekly_change'] as $option): ?><option value="<?= $option ?>" <?= $values['weekly_change'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.weekly_change.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('weekly_change') ?></div>

                    <div class="form-row"><div class="col-12 col-md-6"><div class="form-group"><label for="audience_focus" class="font-weight-bold"><?= l('ai_plan.audience_focus') ?></label><input id="audience_focus" type="text" name="audience_focus" class="form-control" maxlength="120" value="<?= htmlspecialchars($values['audience_focus'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= l('ai_plan.audience_focus_placeholder') ?>" /></div></div><div class="col-12 col-md-6"><div class="form-group"><label for="product_focus" class="font-weight-bold"><?= l('ai_plan.product_focus') ?></label><input id="product_focus" type="text" name="product_focus" class="form-control" maxlength="120" value="<?= htmlspecialchars($values['product_focus'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= l('ai_plan.product_focus_placeholder') ?>" /></div></div></div>

                    <div class="form-group mb-0"><label for="notes" class="font-weight-bold"><?= l('ai_plan.notes') ?></label><textarea id="notes" name="notes" class="form-control" rows="4" maxlength="1000" placeholder="<?= l('ai_plan.notes_placeholder') ?>"><?= htmlspecialchars($values['notes'], ENT_QUOTES, 'UTF-8') ?></textarea></div>

                    <div class="mt-4 d-flex flex-wrap align-items-center" style="gap:.75rem;"><button type="submit" name="save_profile" value="1" class="btn btn-primary"><i class="fas fa-save fa-sm mr-1"></i> <?= l('ai_plan.save') ?></button><span class="text-muted small"><?= l('ai_plan.form_footer') ?></span></div>
                </form>
            </div></div>

            <div class="card ai-plan-card"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;"><div><h2 class="h5 mb-1"><?= l('ai_plan.optional_tool_title') ?></h2><p class="text-muted mb-0"><?= l('ai_plan.optional_tool_text') ?></p></div><?php if($data->latest_app_review): ?><div class="small text-muted"><?= l('ai_plan.plan_generated_at') ?>: <?= \Altum\Date::get($data->latest_app_review['generated_at'], 2) ?></div><?php endif ?></div>
                <div class="ai-plan-inline-meta">
                    <span class="ai-plan-chip active"><?= l($data->app_review_access_payload['plan_label_key']) ?></span>
                    <span class="ai-plan-chip <?= $data->app_review_access_payload['can_select_any_app'] ? 'active' : '' ?>"><?= $data->app_review_access_payload['can_select_any_app'] ? l('ai_plan.app_review_scope_multiple') : l('ai_plan.app_review_scope_main') ?></span>
                    <span class="ai-plan-chip"><?= !empty($data->app_review_access_payload['is_admin_testing']) ? l('ai_plan.app_review_frequency_unlimited') : sprintf(l('ai_plan.app_review_frequency_days'), (int) ($data->app_review_access_payload['cooldown_days'] ?? 7)) ?></span>
                </div>
                <div class="ai-plan-review-grid">
                    <div class="ai-plan-review-box">
                        <?php if(!$data->is_profile_complete): ?>
                            <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.app_review_locked_profile_title') ?></div><div class="text-muted small mb-0"><?= l('ai_plan.app_review_locked_profile') ?></div></div>
                        <?php else: ?>
                            <form action="<?= $ai_plan_section_urls['profile'] ?>" method="post" role="form">
                                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                                <?php if(!empty($data->has_admin_testing_access)): ?>
                                    <div class="ai-plan-soft-box mb-3"><div class="font-weight-bold mb-1"><?= l('ai_plan.app_review_admin_testing_title') ?></div><div class="text-muted small mb-0"><?= l('ai_plan.app_review_admin_testing_text') ?></div></div>
                                <?php endif ?>
                                <?php if($data->app_review_is_locked): ?>
                                    <div class="ai-plan-lock-box mb-3"><div class="font-weight-bold mb-2"><?= l('ai_plan.app_review_locked_cooldown_title') ?></div><div class="text-muted small mb-0"><?= sprintf(l('ai_plan.app_review_locked_cooldown_short'), $data->app_review_countdown_days ?? 0) ?></div></div>
                                <?php else: ?>
                                    <div class="small text-muted mb-3"><?= l('ai_plan.app_review_helper') ?></div>
                                <?php endif ?>

                                <?php if(!empty($data->app_review_access_payload['can_select_any_app'])): ?>
                                    <div class="form-group">
                                        <label for="app_review_selected_link_id" class="font-weight-bold"><?= l('ai_plan.app_review_select_app') ?></label>
                                        <select id="app_review_selected_link_id" name="app_review_selected_link_id" class="custom-select">
                                            <option value=""><?= l('global.choose') ?></option>
                                            <?php foreach(($data->app_review_available_apps ?? []) as $app_option): ?>
                                                <option value="<?= (int) $app_option['link_id'] ?>" data-public-url="<?= htmlspecialchars((string) ($app_option['public_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-app-label="<?= htmlspecialchars((string) ($app_option['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-app-url="<?= htmlspecialchars((string) ($app_option['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= (int) ($data->app_review_selected_link_id ?? 0) === (int) $app_option['link_id'] ? 'selected="selected"' : null ?>><?= htmlspecialchars((string) $app_option['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                <?php elseif(!empty($data->app_review_selected_app)): ?>
                                    <div class="ai-plan-soft-box mb-3">
                                        <div class="small text-muted mb-1"><?= l('ai_plan.app_review_main_app') ?></div>
                                        <div class="font-weight-bold"><?= htmlspecialchars((string) (($data->app_review_selected_app['name'] ?? '') ?: ($data->app_review_selected_app['url'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if(!empty($data->app_review_selected_app['url'])): ?><div class="small text-muted mt-1"><?= htmlspecialchars((string) $data->app_review_selected_app['url'], ENT_QUOTES, 'UTF-8') ?></div><?php endif ?>
                                    </div>
                                <?php endif ?>

                                <div class="form-group mb-0">
                                    <label for="app_review_context" class="font-weight-bold"><?= l('ai_plan.app_review_context') ?></label>
                                    <textarea id="app_review_context" name="app_review_context" class="form-control" rows="4" maxlength="800" placeholder="<?= l('ai_plan.app_review_context_placeholder') ?>"><?= htmlspecialchars((string) ($data->app_review_context ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>

                                <div class="mt-4 d-flex flex-wrap align-items-center" style="gap:.75rem;">
                                    <button type="submit" name="generate_app_review" value="1" class="btn btn-primary" <?= $data->app_review_is_locked ? 'disabled="disabled"' : null ?>><i class="fas fa-search fa-sm mr-1"></i> <?= l('ai_plan.app_review_generate') ?></button>
                                    <span class="text-muted small"><?= $data->app_review_is_locked ? sprintf(l('ai_plan.app_review_locked_cooldown_short'), $data->app_review_countdown_days ?? 0) : l('ai_plan.app_review_footer') ?></span>
                                </div>
                            </form>
                        <?php endif ?>
                    </div>

                    <div class="ai-plan-preview-card">
                        <div class="ai-plan-preview-header">
                            <div>
                                <div class="small text-muted mb-1"><?= l('ai_plan.app_review_preview_title') ?></div>
                                <div class="font-weight-bold" id="ai-plan-preview-title"><?= htmlspecialchars($app_review_preview_label ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="ai-plan-preview-meta mt-1" id="ai-plan-preview-url"><?= htmlspecialchars((string) (($app_review_selected_app['url'] ?? '') ?: ($data->latest_app_review['selected_app_url'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <a id="ai-plan-preview-open" href="<?= htmlspecialchars($app_review_preview_url ?: '#', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm <?= $app_review_preview_url ? null : 'disabled' ?>"><?= l('ai_plan.app_review_preview_open') ?></a>
                        </div>

                        <div class="ai-plan-preview-frame-wrap" id="ai-plan-preview-frame-wrap" <?= $app_review_preview_url ? null : 'style="display:none;"' ?>>
                            <iframe id="ai-plan-preview-frame" class="ai-plan-preview-frame" <?= $app_review_preview_url ? 'src="' . htmlspecialchars($app_review_preview_url, ENT_QUOTES, 'UTF-8') . '"' : null ?> loading="lazy" title="<?= htmlspecialchars($app_review_preview_label ?: 'App preview', ENT_QUOTES, 'UTF-8') ?>"></iframe>
                        </div>

                        <div class="ai-plan-preview-empty" id="ai-plan-preview-empty" <?= $app_review_preview_url ? 'style="display:none;"' : null ?>>
                            <div>
                                <div class="font-weight-bold mb-2"><?= l('ai_plan.app_review_preview_empty_title') ?></div>
                                <div class="text-muted small mb-0"><?= l('ai_plan.app_review_preview_empty_text') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if($data->latest_app_review): ?>
                    <div class="ai-plan-review-highlight mt-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:.75rem;">
                            <div>
                                <div class="small text-muted mb-1"><?= l('ai_plan.app_review_recommendation_title') ?></div>
                                <?php if(!empty($data->latest_app_review['selected_app_name']) || !empty($data->latest_app_review['selected_app_url'])): ?>
                                    <div class="font-weight-bold"><?= htmlspecialchars((string) (($data->latest_app_review['selected_app_name'] ?? '') ?: ($data->latest_app_review['selected_app_url'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif ?>
                            </div>
                            <div class="text-muted small"><?= l('ai_plan.plan_generated_at') ?>: <?= \Altum\Date::get($data->latest_app_review['generated_at'], 2) ?></div>
                        </div>

                        <div class="ai-plan-review-highlight-grid">
                            <div>
                                <div class="ai-plan-review-highlight-label"><?= l('ai_plan.app_review_bottleneck') ?></div>
                                <div class="ai-plan-review-highlight-copy font-weight-bold"><?= htmlspecialchars((string) ($data->latest_app_review['biggest_bottleneck'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div>
                                <div class="ai-plan-review-highlight-label"><?= l('ai_plan.app_review_top_recommendation') ?></div>
                                <div class="ai-plan-review-highlight-copy"><?= htmlspecialchars((string) ($data->latest_app_review['top_recommendation'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div>
                                <div class="ai-plan-review-highlight-label"><?= l('ai_plan.app_review_weekly_focus') ?></div>
                                <div class="ai-plan-review-highlight-copy"><?= htmlspecialchars((string) (($data->latest_app_review['weekly_focus'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="ai-plan-review-disclosure-stack mt-3">
                        <details class="ai-plan-review-disclosure">
                            <summary>
                                <span><?= l('ai_plan.app_review_summary') ?></span>
                                <span class="ai-plan-review-disclosure-note"><?= l('ai_plan.app_review_preview_text') ?></span>
                            </summary>
                            <div class="ai-plan-review-disclosure-body">
                                <p class="mb-0"><?= htmlspecialchars((string) ($data->latest_app_review['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </details>

                        <details class="ai-plan-review-disclosure">
                            <summary>
                                <span><?= l('ai_plan.app_review_priority_actions') ?></span>
                                <span class="ai-plan-review-disclosure-note"><?= nr(count((array) ($data->latest_app_review['priority_actions'] ?? []))) ?></span>
                            </summary>
                            <div class="ai-plan-review-disclosure-body">
                                <ul class="ai-plan-review-list mb-0">
                                    <?php foreach(($data->latest_app_review['priority_actions'] ?? []) as $action): ?>
                                        <li><?= htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach ?>
                                </ul>
                            </div>
                        </details>

                        <details class="ai-plan-review-disclosure">
                            <summary>
                                <span><?= l('ai_plan.app_review_block_order') ?></span>
                                <span class="ai-plan-review-disclosure-note"><?= nr(count((array) ($data->latest_app_review['ideal_block_order'] ?? []))) ?></span>
                            </summary>
                            <div class="ai-plan-review-disclosure-body">
                                <div class="ai-plan-review-order mb-0">
                                    <?php foreach(($data->latest_app_review['ideal_block_order'] ?? []) as $index => $item): ?>
                                        <div class="ai-plan-review-order-item"><span class="ai-plan-review-order-step"><?= $index + 1 ?></span><div><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></div></div>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        </details>

                        <?php if(!empty($data->latest_app_review['design_notes'])): ?>
                            <details class="ai-plan-review-disclosure">
                                <summary>
                                    <span><?= l('ai_plan.app_review_design_notes') ?></span>
                                    <span class="ai-plan-review-disclosure-note"><?= nr(count((array) ($data->latest_app_review['design_notes'] ?? []))) ?></span>
                                </summary>
                                <div class="ai-plan-review-disclosure-body">
                                    <ul class="ai-plan-review-list mb-0">
                                        <?php foreach(($data->latest_app_review['design_notes'] ?? []) as $note): ?>
                                            <li><?= htmlspecialchars((string) $note, ENT_QUOTES, 'UTF-8') ?></li>
                                        <?php endforeach ?>
                                    </ul>
                                </div>
                            </details>
                        <?php endif ?>

                        <?php if(!empty($data->latest_app_review['keep_doing'])): ?>
                            <details class="ai-plan-review-disclosure">
                                <summary>
                                    <span><?= l('ai_plan.app_review_keep_doing') ?></span>
                                    <span class="ai-plan-review-disclosure-note"><?= nr(count((array) ($data->latest_app_review['keep_doing'] ?? []))) ?></span>
                                </summary>
                                <div class="ai-plan-review-disclosure-body">
                                    <ul class="ai-plan-review-list mb-0">
                                        <?php foreach(($data->latest_app_review['keep_doing'] ?? []) as $note): ?>
                                            <li><?= htmlspecialchars((string) $note, ENT_QUOTES, 'UTF-8') ?></li>
                                        <?php endforeach ?>
                                    </ul>
                                </div>
                            </details>
                        <?php endif ?>
                    </div>
                <?php endif ?>
            </div></div>
            <?php endif ?>

            <?php if($ai_plan_active_section === 'weekly'): ?>
            <div class="card ai-plan-card"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;"><div><h2 class="h5 mb-1"><?= l('ai_plan.weekly_title') ?></h2><p class="text-muted mb-0"><?= l('ai_plan.weekly_text') ?></p></div><?php if(!empty($data->latest_weekly_checkin['submitted_at'])): ?><div class="small text-muted"><?= l('ai_plan.last_updated') ?>: <?= \Altum\Date::get($data->latest_weekly_checkin['submitted_at'], 2) ?></div><?php endif ?></div>

                <?php if(!$data->is_profile_complete): ?>
                    <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.weekly_locked_profile_title') ?></div><div class="text-muted small mb-0"><?= l('ai_plan.weekly_locked_profile') ?></div></div>
                <?php elseif(!$data->is_weekly_plan_eligible): ?>
                    <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.weekly_locked_signal_title') ?></div><div class="text-muted small mb-0"><?= sprintf(l('ai_plan.weekly_locked_signal'), 15, nr($data->current_clicks_30d)) ?></div></div>
                <?php elseif($data->weekly_is_locked): ?>
                    <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.weekly_locked_cooldown_title') ?></div><div class="text-muted small mb-0"><?= sprintf(l('ai_plan.weekly_locked_cooldown_short'), $data->weekly_countdown_days ?? 0) ?></div></div>
                <?php else: ?>
                    <form action="<?= $ai_plan_section_urls['weekly'] ?>" method="post" role="form">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                        <div class="form-row">
                            <div class="col-12 col-md-6"><div class="form-group"><label for="weekly_priority" class="font-weight-bold"><?= l('ai_plan.weekly_priority') ?></label><select id="weekly_priority" name="weekly_priority" class="custom-select <?= \Altum\Alerts::has_field_errors('weekly_priority') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->weekly_options['weekly_priority'] as $option): ?><option value="<?= $option ?>" <?= $weekly_values['weekly_priority'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.weekly_priority.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('weekly_priority') ?></div></div>
                            <div class="col-12 col-md-6"><div class="form-group"><label for="weekly_energy" class="font-weight-bold"><?= l('ai_plan.weekly_energy') ?></label><select id="weekly_energy" name="weekly_energy" class="custom-select <?= \Altum\Alerts::has_field_errors('weekly_energy') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->weekly_options['weekly_energy'] as $option): ?><option value="<?= $option ?>" <?= $weekly_values['weekly_energy'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.weekly_energy.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('weekly_energy') ?></div></div>
                        </div>

                        <div class="form-row">
                            <div class="col-12 col-md-6"><div class="form-group"><label for="content_commitment" class="font-weight-bold"><?= l('ai_plan.content_commitment') ?></label><select id="content_commitment" name="content_commitment" class="custom-select <?= \Altum\Alerts::has_field_errors('content_commitment') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->weekly_options['content_commitment'] as $option): ?><option value="<?= $option ?>" <?= $weekly_values['content_commitment'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.content_commitment.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('content_commitment') ?></div></div>
                            <div class="col-12 col-md-6"><div class="form-group"><label for="follow_up_volume" class="font-weight-bold"><?= l('ai_plan.follow_up_volume') ?></label><select id="follow_up_volume" name="follow_up_volume" class="custom-select <?= \Altum\Alerts::has_field_errors('follow_up_volume') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->weekly_options['follow_up_volume'] as $option): ?><option value="<?= $option ?>" <?= $weekly_values['follow_up_volume'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.follow_up_volume.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('follow_up_volume') ?></div></div>
                        </div>

                        <div class="form-group"><label for="ai_need" class="font-weight-bold"><?= l('ai_plan.ai_need') ?></label><select id="ai_need" name="ai_need" class="custom-select <?= \Altum\Alerts::has_field_errors('ai_need') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->weekly_options['ai_need'] as $option): ?><option value="<?= $option ?>" <?= $weekly_values['ai_need'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.ai_need.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('ai_need') ?></div>

                        <div class="form-group"><label for="adaptive_answer" class="font-weight-bold"><?= $data->adaptive_question['label'] ?></label><textarea id="adaptive_answer" name="adaptive_answer" class="form-control <?= \Altum\Alerts::has_field_errors('adaptive_answer') ? 'is-invalid' : null ?>" rows="3" maxlength="800" placeholder="<?= $data->adaptive_question['placeholder'] ?>"><?= htmlspecialchars($weekly_values['adaptive_answer'], ENT_QUOTES, 'UTF-8') ?></textarea><?= \Altum\Alerts::output_field_error('adaptive_answer') ?></div>

                        <div class="form-group mb-0"><label for="weekly_context" class="font-weight-bold"><?= l('ai_plan.weekly_context') ?></label><textarea id="weekly_context" name="weekly_context" class="form-control" rows="4" maxlength="800" placeholder="<?= l('ai_plan.weekly_context_placeholder') ?>"><?= htmlspecialchars($weekly_values['weekly_context'], ENT_QUOTES, 'UTF-8') ?></textarea></div>

                        <div class="mt-4 d-flex flex-wrap align-items-center" style="gap:.75rem;"><button type="submit" name="save_weekly_checkin" value="1" class="btn btn-primary"><i class="fas fa-calendar-check fa-sm mr-1"></i> <?= l('ai_plan.weekly_save') ?></button><span class="text-muted small"><?= l('ai_plan.weekly_footer_phase_3') ?></span></div>
                    </form>
                <?php endif ?>
            </div></div>
            <?php endif ?>

            <?php if($ai_plan_active_section === 'plan'): ?>
            <div class="card ai-plan-card"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;"><div><h2 class="h5 mb-1"><?= l('ai_plan.plan_title') ?></h2><p class="text-muted mb-0"><?= l('ai_plan.plan_text') ?></p></div><?php if($data->latest_weekly_plan): ?><div class="small text-muted"><?= l('ai_plan.plan_generated_at') ?>: <?= \Altum\Date::get($data->latest_weekly_plan['generated_at'], 2) ?></div><?php endif ?></div>

                <?php if(!$data->latest_weekly_checkin): ?>
                    <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.plan_empty_title') ?></div><div class="text-muted small mb-0"><?= l('ai_plan.plan_empty_text') ?></div></div>
                <?php elseif(!$data->latest_weekly_plan): ?>
                    <div class="ai-plan-lock-box mb-3"><div class="font-weight-bold mb-2"><?= l('ai_plan.plan_pending_title') ?></div><div class="text-muted small mb-0"><?= l('ai_plan.plan_pending_text') ?></div></div>
                    <form action="<?= $ai_plan_section_urls['plan'] ?>" method="post" role="form">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                        <button type="submit" name="regenerate_ai_plan" value="1" class="btn btn-outline-primary"><i class="fas fa-magic fa-sm mr-1"></i> <?= l('ai_plan.plan_generate') ?></button>
                    </form>
                <?php else: ?>
                    <?php if(!empty($data->feedback_loop_payload['has_feedback'])): ?>
                        <!-- Custom code: FC-2026-03-31: AI plan feedback loop summary -->
                        <div class="ai-plan-outcome-box mb-3">
                            <div class="h6 mb-1"><?= l('ai_plan.feedback_loop_title') ?></div>
                            <div class="text-muted small mb-3"><?= l('ai_plan.feedback_loop_text') ?></div>
                            <?php if(!empty($data->feedback_loop_payload['previous_focus'])): ?>
                                <div class="small text-muted mb-1"><?= l('ai_plan.feedback_loop_previous_focus') ?></div>
                                <div class="mb-3 font-weight-bold"><?= htmlspecialchars($data->feedback_loop_payload['previous_focus'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif ?>
                            <div class="small text-muted mb-1"><?= l('ai_plan.feedback_loop_completion') ?></div>
                            <div class="mb-3"><?= htmlspecialchars($data->feedback_loop_payload['completion_level'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-muted mb-1"><?= l('ai_plan.feedback_loop_best_response') ?></div>
                            <div class="mb-3"><?= htmlspecialchars($data->feedback_loop_payload['best_response'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-muted mb-1"><?= l('ai_plan.feedback_loop_blocker') ?></div>
                            <div class="mb-3"><?= htmlspecialchars($data->feedback_loop_payload['main_blocker_now'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-muted mb-1"><?= l('ai_plan.feedback_loop_adjustment') ?></div>
                            <div class="mb-0"><?= htmlspecialchars($data->feedback_loop_payload['next_adjustment'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <!-- /Custom code: FC-2026-03-31 -->
                    <?php endif ?>

                    <div class="mb-3">
                        <?php if(!empty($data->latest_weekly_plan['coach_intro'])): ?><div class="small text-muted mb-1"><?= l('ai_plan.plan_coach_intro') ?></div><p class="mb-3"><?= htmlspecialchars($data->latest_weekly_plan['coach_intro'], ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
                        <div class="small text-muted mb-1"><?= l('ai_plan.plan_focus') ?></div><h3 class="h5 mb-2"><?= htmlspecialchars($data->latest_weekly_plan['headline'], ENT_QUOTES, 'UTF-8') ?></h3><p class="text-muted mb-2"><?= htmlspecialchars($data->latest_weekly_plan['summary'], ENT_QUOTES, 'UTF-8') ?></p><div class="font-weight-bold mb-2"><?= htmlspecialchars($data->latest_weekly_plan['focus'], ENT_QUOTES, 'UTF-8') ?></div><div class="d-flex flex-wrap" style="gap:.45rem;"><?php foreach($data->latest_weekly_plan['priority_channels'] as $priority_channel): ?><span class="ai-plan-pill"><?= htmlspecialchars($priority_channel, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach ?></div>
                        <?php if(!empty($data->latest_weekly_plan['brutal_truth'])): ?><div class="small text-muted mt-3 mb-1"><?= l('ai_plan.plan_brutal_truth') ?></div><p class="mb-2 font-weight-bold" style="color:#f8fafc;"><?= htmlspecialchars($data->latest_weekly_plan['brutal_truth'], ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
                        <?php if(!empty($data->latest_weekly_plan['power_move'])): ?><div class="small text-muted mt-3 mb-1"><?= l('ai_plan.plan_power_move') ?></div><p class="text-info mb-2" style="font-weight:700;"><?= htmlspecialchars($data->latest_weekly_plan['power_move'], ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
                        <?php if(!empty($data->latest_weekly_plan['why_this_week'])): ?><div class="small text-muted mt-3 mb-1"><?= l('ai_plan.plan_why_this_week') ?></div><p class="text-muted mb-0"><?= htmlspecialchars($data->latest_weekly_plan['why_this_week'], ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
                    </div>

                    <div class="ai-plan-plan-block">
                    <div class="ai-plan-day-stream">
                        <?php foreach($data->latest_weekly_plan['daily_plan'] as $day_plan): ?>
                            <div class="ai-plan-day-row">
                                <div class="ai-plan-day-side">
                                    <div class="ai-plan-day-badge"><?= htmlspecialchars($day_plan['day'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <h4 class="ai-plan-day-title"><?= htmlspecialchars($day_plan['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                </div>
                                <div class="ai-plan-day-marker"><span class="ai-plan-day-node"></span></div>
                                <div class="ai-plan-day-content ai-plan-day-card">
                                    <ul class="ai-plan-task-list"><?php foreach($day_plan['tasks'] as $task): ?><li class="ai-plan-task-item"><?= htmlspecialchars($task, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach ?></ul>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>

                    <div class="ai-plan-advice-stack">
                        <div class="ai-plan-advice-row is-content">
                            <div class="ai-plan-advice-side">
                                <div>
                                    <div class="ai-plan-advice-kicker">Coach alat</div>
                                    <div class="ai-plan-advice-title"><?= l('ai_plan.plan_content_ideas') ?></div>
                                </div>
                                <div class="ai-plan-advice-note">Kratke i primjenjive teme koje možeš pretvoriti u objavu, story ili jednostavan razgovor ovaj tjedan.</div>
                            </div>
                            <div class="ai-plan-advice-copy"><?php if(!empty($data->latest_weekly_plan['content_ideas'])): ?><ul class="ai-plan-task-list"><?php foreach($data->latest_weekly_plan['content_ideas'] as $idea): ?><li class="ai-plan-task-item"><?= htmlspecialchars($idea, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach ?></ul><?php else: ?><div class="text-muted small mb-0"><?= l('ai_plan.plan_content_ideas_empty') ?></div><?php endif ?></div>
                        </div>

                        <div class="ai-plan-advice-row is-coach">
                            <div class="ai-plan-advice-side">
                                <div>
                                    <div class="ai-plan-advice-kicker">Coach pogled</div>
                                    <div class="ai-plan-advice-title"><?= l('ai_plan.plan_coach_ideas') ?></div>
                                </div>
                                <div class="ai-plan-advice-note"><?php if(!empty($data->latest_weekly_plan['encouragement'])): ?><?= htmlspecialchars($data->latest_weekly_plan['encouragement'], ENT_QUOTES, 'UTF-8') ?><?php else: ?>Jasan smjer koji te drži fokusirano bez previše kompliciranja.<?php endif ?></div>
                            </div>
                            <div class="ai-plan-advice-copy"><?php if(!empty($data->latest_weekly_plan['coach_ideas'])): ?><ul class="ai-plan-task-list"><?php foreach($data->latest_weekly_plan['coach_ideas'] as $item): ?><li class="ai-plan-task-item"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach ?></ul><?php else: ?><div class="text-muted small mb-0"><?= l('ai_plan.plan_content_ideas_empty') ?></div><?php endif ?></div>
                        </div>

                        <div class="ai-plan-advice-row is-boundary">
                            <div class="ai-plan-advice-side">
                                <div>
                                    <div class="ai-plan-advice-kicker">Za mirniji fokus</div>
                                    <div class="ai-plan-advice-title"><?= l('ai_plan.plan_do_not_do') ?></div>
                                </div>
                                <div class="ai-plan-advice-note">Stvari koje je ovaj tjedan bolje ne širiti ni ne komplicirati kako bi glavni fokus ostao čist.</div>
                            </div>
                            <div class="ai-plan-advice-copy"><?php if(!empty($data->latest_weekly_plan['do_not_do'])): ?><ul class="ai-plan-task-list"><?php foreach($data->latest_weekly_plan['do_not_do'] as $item): ?><li class="ai-plan-task-item"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach ?></ul><?php else: ?><div class="text-muted small mb-0"><?= l('ai_plan.plan_do_not_do_empty') ?></div><?php endif ?></div>
                        </div>
                    </div>
                    </div>

                    <form action="<?= $ai_plan_section_urls['plan'] ?>" method="post" role="form" class="mt-2">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                        <button type="submit" name="regenerate_ai_plan" value="1" class="btn btn-outline-primary"><i class="fas fa-sync-alt fa-sm mr-1"></i> <?= l('ai_plan.plan_regenerate') ?></button>
                    </form>

                <?php endif ?>
            </div></div>

            <?php if($data->latest_weekly_plan): ?>
                <div class="card ai-plan-card"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;">
                        <div>
                            <h2 class="h5 mb-1"><?= l('ai_plan.outcome_title') ?></h2>
                            <p class="text-muted mb-0"><?= l('ai_plan.outcome_text') ?></p>
                        </div>
                        <?php if($data->latest_weekly_outcome): ?>
                            <div class="small text-muted"><?= l('ai_plan.last_updated') ?>: <?= \Altum\Date::get($data->latest_weekly_outcome['submitted_at'], 2) ?></div>
                        <?php endif ?>
                    </div>

                    <?php if($data->latest_weekly_outcome): ?>
                        <div class="ai-plan-outcome-box mb-3">
                            <div class="small text-muted mb-1"><?= l('ai_plan.outcome_completion_level') ?></div>
                            <div class="font-weight-bold mb-3"><?= l('ai_plan.option.completion_level.' . $data->latest_weekly_outcome['completion_level']) ?></div>
                            <div class="small text-muted mb-1"><?= l('ai_plan.outcome_best_response') ?></div>
                            <div class="mb-3"><?= htmlspecialchars($data->latest_weekly_outcome['best_response'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-muted mb-1"><?= l('ai_plan.outcome_main_blocker_now') ?></div>
                            <div class="mb-3"><?= htmlspecialchars($data->latest_weekly_outcome['main_blocker_now'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-muted mb-1"><?= l('ai_plan.outcome_biggest_lesson') ?></div>
                            <div class="mb-3"><?= htmlspecialchars($data->latest_weekly_outcome['biggest_lesson'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-muted mb-1"><?= l('ai_plan.outcome_next_adjustment') ?></div>
                            <div class="mb-0"><?= htmlspecialchars($data->latest_weekly_outcome['next_adjustment'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    <?php endif ?>

                    <form action="<?= $ai_plan_section_urls['plan'] ?>" method="post" role="form">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                        <div class="form-group">
                            <label for="completion_level" class="font-weight-bold"><?= l('ai_plan.outcome_completion_level') ?></label>
                            <select id="completion_level" name="completion_level" class="custom-select <?= \Altum\Alerts::has_field_errors('completion_level') ? 'is-invalid' : null ?>">
                                <option value=""><?= l('global.choose') ?></option>
                                <?php foreach(['strong_progress', 'partial_progress', 'low_execution', 'not_started'] as $option): ?>
                                    <option value="<?= $option ?>" <?= (($data->latest_weekly_outcome['completion_level'] ?? '') === $option) ? 'selected="selected"' : null ?>><?= l('ai_plan.option.completion_level.' . $option) ?></option>
                                <?php endforeach ?>
                            </select>
                            <?= \Altum\Alerts::output_field_error('completion_level') ?>
                        </div>

                        <div class="form-group">
                            <label for="best_response" class="font-weight-bold"><?= l('ai_plan.outcome_best_response') ?></label>
                            <textarea id="best_response" name="best_response" rows="3" maxlength="800" class="form-control <?= \Altum\Alerts::has_field_errors('best_response') ? 'is-invalid' : null ?>" placeholder="<?= l('ai_plan.outcome_best_response_placeholder') ?>"><?= htmlspecialchars((string) ($data->latest_weekly_outcome['best_response'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <?= \Altum\Alerts::output_field_error('best_response') ?>
                        </div>

                        <div class="form-group">
                            <label for="main_blocker_now" class="font-weight-bold"><?= l('ai_plan.outcome_main_blocker_now') ?></label>
                            <textarea id="main_blocker_now" name="main_blocker_now" rows="3" maxlength="800" class="form-control <?= \Altum\Alerts::has_field_errors('main_blocker_now') ? 'is-invalid' : null ?>" placeholder="<?= l('ai_plan.outcome_main_blocker_now_placeholder') ?>"><?= htmlspecialchars((string) ($data->latest_weekly_outcome['main_blocker_now'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <?= \Altum\Alerts::output_field_error('main_blocker_now') ?>
                        </div>

                        <div class="form-group">
                            <label for="biggest_lesson" class="font-weight-bold"><?= l('ai_plan.outcome_biggest_lesson') ?></label>
                            <textarea id="biggest_lesson" name="biggest_lesson" rows="3" maxlength="800" class="form-control <?= \Altum\Alerts::has_field_errors('biggest_lesson') ? 'is-invalid' : null ?>" placeholder="<?= l('ai_plan.outcome_biggest_lesson_placeholder') ?>"><?= htmlspecialchars((string) ($data->latest_weekly_outcome['biggest_lesson'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <?= \Altum\Alerts::output_field_error('biggest_lesson') ?>
                        </div>

                        <div class="form-group mb-0">
                            <label for="next_adjustment" class="font-weight-bold"><?= l('ai_plan.outcome_next_adjustment') ?></label>
                            <textarea id="next_adjustment" name="next_adjustment" rows="3" maxlength="800" class="form-control <?= \Altum\Alerts::has_field_errors('next_adjustment') ? 'is-invalid' : null ?>" placeholder="<?= l('ai_plan.outcome_next_adjustment_placeholder') ?>"><?= htmlspecialchars((string) ($data->latest_weekly_outcome['next_adjustment'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <?= \Altum\Alerts::output_field_error('next_adjustment') ?>
                        </div>

                        <div class="mt-4 d-flex flex-wrap align-items-center" style="gap:.75rem;">
                            <button type="submit" name="save_weekly_outcome" value="1" class="btn btn-primary"><i class="fas fa-check-circle fa-sm mr-1"></i> <?= l('ai_plan.outcome_save') ?></button>
                            <span class="text-muted small"><?= l('ai_plan.outcome_footer') ?></span>
                        </div>
                    </form>
                </div></div>
            <?php endif ?>
            <?php endif ?>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const appSelect = document.getElementById('app_review_selected_link_id');
                    if(!appSelect) {
                        return;
                    }

                    const previewTitle = document.getElementById('ai-plan-preview-title');
                    const previewUrl = document.getElementById('ai-plan-preview-url');
                    const previewOpen = document.getElementById('ai-plan-preview-open');
                    const previewFrame = document.getElementById('ai-plan-preview-frame');
                    const previewWrap = document.getElementById('ai-plan-preview-frame-wrap');
                    const previewEmpty = document.getElementById('ai-plan-preview-empty');
                    const openLabel = <?= json_encode(l('ai_plan.app_review_preview_open')) ?>;

                    const updatePreview = function() {
                        const selectedOption = appSelect.options[appSelect.selectedIndex];
                        if(!selectedOption) {
                            return;
                        }

                        const publicUrl = selectedOption.dataset.publicUrl || '';
                        const appLabel = selectedOption.dataset.appLabel || selectedOption.text || '-';
                        const appUrl = selectedOption.dataset.appUrl || '';

                        if(previewTitle) {
                            previewTitle.textContent = appLabel;
                        }

                        if(previewUrl) {
                            previewUrl.textContent = appUrl;
                        }

                        if(publicUrl) {
                            if(previewFrame) {
                                previewFrame.src = publicUrl;
                                previewFrame.title = appLabel;
                            }

                            if(previewWrap) {
                                previewWrap.style.display = '';
                            }

                            if(previewEmpty) {
                                previewEmpty.style.display = 'none';
                            }

                            if(previewOpen) {
                                if(previewOpen.tagName === 'A') {
                                    previewOpen.href = publicUrl;
                                    previewOpen.classList.remove('disabled');
                                } else {
                                    previewOpen.textContent = openLabel;
                                }
                            }
                        } else {
                            if(previewFrame) {
                                previewFrame.removeAttribute('src');
                            }

                            if(previewWrap) {
                                previewWrap.style.display = 'none';
                            }

                            if(previewEmpty) {
                                previewEmpty.style.display = 'flex';
                            }

                            if(previewOpen && previewOpen.tagName === 'A') {
                                previewOpen.href = '#';
                                previewOpen.classList.add('disabled');
                            }
                        }
                    };

                    appSelect.addEventListener('change', updatePreview);
                    updatePreview();
                });
            </script>
        </div>

        <div class="col-12 col-xl-4 mb-4">
            <div class="ai-plan-sticky-column">
            <div class="card ai-plan-card mb-3"><div class="card-body"><h2 class="h5 mb-2"><?= l('ai_plan.weekly_status_title') ?></h2><?php if($data->latest_weekly_checkin): ?><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= l('ai_plan.last_updated') ?></div><div class="font-weight-bold"><?= \Altum\Date::get($data->latest_weekly_checkin['submitted_at'], 2) ?></div></div><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= l('ai_plan.weekly_priority') ?></div><div><?= l('ai_plan.option.weekly_priority.' . $data->latest_weekly_checkin['weekly_priority']) ?></div></div><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= l('ai_plan.ai_need') ?></div><div><?= l('ai_plan.option.ai_need.' . $data->latest_weekly_checkin['ai_need']) ?></div></div><?php if(!empty($data->latest_weekly_checkin['adaptive_question_key']) && !empty($data->latest_weekly_checkin['adaptive_answer'])): ?><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= l('ai_plan.weekly_adaptive_title') ?></div><div class="text-muted small mb-1"><?= l('ai_plan.adaptive_question.' . $data->latest_weekly_checkin['adaptive_question_key']) ?></div><div class="small mb-0"><?= htmlspecialchars($data->latest_weekly_checkin['adaptive_answer'], ENT_QUOTES, 'UTF-8') ?></div></div><?php endif ?><?php if(!empty($data->latest_weekly_checkin['weekly_context'])): ?><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= l('ai_plan.weekly_context') ?></div><div class="text-muted small mb-0"><?= htmlspecialchars($data->latest_weekly_checkin['weekly_context'], ENT_QUOTES, 'UTF-8') ?></div></div><?php endif ?><?php else: ?><div class="text-muted small mb-0"><?= l('ai_plan.weekly_empty') ?></div><?php endif ?></div></div>
            <div class="card ai-plan-card mb-3"><div class="card-body"><h2 class="h5 mb-2"><?= l('ai_plan.analytics_title') ?></h2><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.analytics_top_source') ?></span><strong><?= htmlspecialchars($data->analytics_payload['top_source_label'], ENT_QUOTES, 'UTF-8') ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.analytics_top_country') ?></span><strong><?= htmlspecialchars($data->analytics_payload['top_country_label'], ENT_QUOTES, 'UTF-8') ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.analytics_top_device') ?></span><strong><?= htmlspecialchars($data->analytics_payload['top_device_label'], ENT_QUOTES, 'UTF-8') ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.analytics_funnel') ?></span><strong><?= sprintf(l('ai_plan.analytics_funnel_value'), nr($data->analytics_payload['funnel']['active_funnels'] ?? 0), nr($data->analytics_payload['funnel']['total_funnels'] ?? 0)) ?></strong></div><div class="small text-muted mt-3 mb-0"><?= l('ai_plan.analytics_help') ?></div></div></div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-31 -->