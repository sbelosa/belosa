<?php defined('ALTUMCODE') || die() ?>

<?php $feature_is_available = $data->feature_is_available ?? true; ?>

<!-- Custom code: FC-2026-03-31: User AI plan phase 1-3 view -->
<style>
    .ai-plan-shell .ai-plan-card { border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 1rem; background: linear-gradient(155deg, rgba(255,255,255,.03), rgba(255,255,255,.01)); box-shadow: 0 1rem 2rem rgba(15,23,42,.12); }
    .ai-plan-shell .ai-plan-hero { position:relative; overflow:hidden; background:radial-gradient(720px 280px at -6% -34%, rgba(45,212,191,.12), transparent 58%), radial-gradient(620px 240px at 82% 18%, rgba(59,130,246,.12), transparent 56%), linear-gradient(155deg, rgba(255,255,255,.035), rgba(255,255,255,.012)); }
    .ai-plan-shell .ai-plan-hero::before { content:''; position:absolute; inset:0; background:linear-gradient(90deg, rgba(255,255,255,.02), transparent 38%, transparent 62%, rgba(255,255,255,.015)); pointer-events:none; }
    .ai-plan-shell .ai-plan-hero-copy { position:relative; z-index:1; max-width:860px; }
    .ai-plan-shell .ai-plan-hero-summary { position:relative; z-index:1; border:1px solid rgba(255,255,255,.08); border-radius:1.2rem; background:linear-gradient(165deg, rgba(255,255,255,.05), rgba(255,255,255,.02)); box-shadow:0 1.1rem 2.4rem rgba(2,6,23,.22), inset 0 1px 0 rgba(255,255,255,.04); backdrop-filter:blur(10px); }
    .ai-plan-shell .ai-plan-hero-summary strong { font-size:1.05rem; color:#f8fafc; }
    .ai-plan-shell .ai-plan-hero .h4 { font-size:1.72rem; line-height:1.12; letter-spacing:-.025em; color:#f8fafc; max-width:none; white-space:nowrap; }
    .ai-plan-shell .ai-plan-hero p.text-muted { font-size:1.12rem; line-height:1.65; color:rgba(203,213,225,.88) !important; max-width:54ch; }
    .ai-plan-shell .ai-plan-hero .btn-primary { padding:.82rem 1.2rem; border-radius:.95rem; box-shadow:0 .95rem 2rem rgba(45,212,191,.16); }
    .ai-plan-shell .ai-plan-chip { display:inline-flex; align-items:center; padding:.48rem .82rem; border-radius:999px; font-size:.75rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; border:1px solid rgba(148,163,184,.22); background:rgba(15,23,42,.28); color:#e2e8f0; box-shadow:inset 0 1px 0 rgba(255,255,255,.04), 0 .35rem .9rem rgba(2,6,23,.18); }
    .ai-plan-shell .ai-plan-chip.active { color:#e0f2fe; background:linear-gradient(135deg, rgba(14,165,233,.34), rgba(59,130,246,.24)); border-color:rgba(56,189,248,.38); }
    .ai-plan-shell .ai-plan-chip.locked { color:#ffedd5; background:linear-gradient(135deg, rgba(249,115,22,.26), rgba(251,146,60,.16)); border-color:rgba(251,146,60,.34); }
    .ai-plan-shell .ai-plan-chip.success { color:#dcfce7; background:linear-gradient(135deg, rgba(34,197,94,.28), rgba(22,163,74,.18)); border-color:rgba(74,222,128,.28); }
    .ai-plan-shell .ai-plan-page-hero { position:relative; overflow:hidden; display:grid; grid-template-columns:minmax(0, 1.15fr) auto; gap:1rem; align-items:center; margin-bottom:1rem; padding:1.15rem 1.2rem; border:1px solid rgba(255,255,255,.08); border-radius:1.2rem; background:radial-gradient(520px 180px at 0% 0%, rgba(45,212,191,.09), transparent 58%), radial-gradient(420px 160px at 100% 0%, rgba(59,130,246,.08), transparent 60%), linear-gradient(155deg, rgba(255,255,255,.03), rgba(255,255,255,.012)); box-shadow:0 1rem 2rem rgba(2,6,23,.14), inset 0 1px 0 rgba(255,255,255,.03); }
    .ai-plan-shell .ai-plan-page-hero::before { content:''; position:absolute; inset:0; pointer-events:none; background:linear-gradient(120deg, rgba(255,255,255,.02), transparent 34%, transparent 70%, rgba(255,255,255,.015)); }
    .ai-plan-shell .ai-plan-page-hero-copy,
    .ai-plan-shell .ai-plan-page-hero-action { position:relative; z-index:1; }
    .ai-plan-shell .ai-plan-page-kicker { display:inline-flex; align-items:center; padding:.34rem .72rem; margin-bottom:.7rem; border-radius:999px; font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#bffdf8; background:linear-gradient(135deg, rgba(13,148,136,.34), rgba(8,145,178,.22)); border:1px solid rgba(45,212,191,.28); box-shadow:0 .65rem 1.5rem rgba(15,118,110,.12); }
    .ai-plan-shell .ai-plan-page-hero-title { font-size:2.15rem; line-height:1.02; letter-spacing:-.04em; color:#f8fafc; margin:0 0 .55rem; }
    .ai-plan-shell .ai-plan-page-hero-text { max-width:64ch; margin:0; font-size:1.02rem; line-height:1.7; color:rgba(203,213,225,.86) !important; }
    .ai-plan-shell .ai-plan-page-hero-action .btn { min-height:3.25rem; padding:.82rem 1.15rem; border-radius:1rem; font-weight:700; box-shadow:0 .8rem 1.6rem rgba(8,145,178,.08); white-space:nowrap; }
    .ai-plan-shell .ai-plan-disabled-link,
    .ai-plan-shell .ai-plan-disabled-link.disabled {
        opacity:.62;
        filter:saturate(.82) brightness(.94);
        box-shadow:none !important;
        cursor:not-allowed;
    }
    .ai-plan-shell .ai-plan-disabled-link:hover,
    .ai-plan-shell .ai-plan-disabled-link.disabled:hover {
        transform:none;
    }
    .ai-plan-shell .ai-plan-option-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(168px, 1fr)); gap:.65rem; }
    .ai-plan-shell .ai-plan-option input { display:none; }
    .ai-plan-shell .ai-plan-option label { width:100%; margin:0; padding:.8rem .9rem; border-radius:.85rem; border:1px solid rgba(148,163,184,.18); background:rgba(255,255,255,.02); font-weight:600; cursor:pointer; transition:border-color .2s ease, background .2s ease, transform .2s ease; }
    .ai-plan-shell .ai-plan-option input:checked + label { border-color:rgba(14,165,233,.38); background:rgba(125,211,252,.12); transform:translateY(-1px); }
    .ai-plan-shell .ai-plan-phase-list { display:grid; gap:.75rem; }
    .ai-plan-shell .ai-plan-phase-item { padding:.9rem 1rem; border-radius:.9rem; border:1px solid rgba(148,163,184,.12); background:rgba(255,255,255,.02); }
    .ai-plan-shell .ai-plan-phase-item.active { border-color:rgba(14,165,233,.25); background:rgba(125,211,252,.08); }
    .ai-plan-shell .ai-plan-stat-row { display:grid; grid-template-columns:minmax(0, 1fr) auto; align-items:start; gap:.9rem; padding:.7rem 0; border-bottom:1px solid rgba(148,163,184,.12); }
    .ai-plan-shell .ai-plan-stat-row > span { min-width:0; }
    .ai-plan-shell .ai-plan-stat-row > strong { min-width:0; max-width:100%; text-align:right; white-space:normal; overflow-wrap:anywhere; }
    .ai-plan-shell .ai-plan-stat-row:last-child { border-bottom:0; padding-bottom:0; }
    .ai-plan-shell .ai-plan-lock-box { border: 1px dashed rgba(148,163,184,.25); border-radius: .9rem; padding: 1rem; background: rgba(15,23,42,.02); }
    .ai-plan-shell .ai-plan-history-item { padding: .85rem 0; border-bottom: 1px solid rgba(148,163,184,.12); }
    .ai-plan-shell .ai-plan-history-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .ai-plan-shell .ai-plan-outcome-box { border: 1px dashed rgba(14,165,233,.18); border-radius: .95rem; padding: 1rem; background: rgba(14,165,233,.03); }
    .ai-plan-shell .ai-plan-day-card { border: 1px solid rgba(148,163,184,.12); border-radius: .9rem; padding: 1rem; background: rgba(255,255,255,.02); height: 100%; }
    .ai-plan-shell .ai-plan-pill { display:inline-flex; align-items:center; padding:.46rem .82rem; border-radius:999px; font-size:.84rem; font-weight:700; line-height:1.35; letter-spacing:.01em; background:linear-gradient(145deg, rgba(31,41,55,.9), rgba(17,24,39,.84)); color:rgba(226,232,240,.94); border:1px solid rgba(148,163,184,.2); box-shadow:inset 0 1px 0 rgba(255,255,255,.04); }
    .ai-plan-shell .ai-plan-pill:hover { border-color:rgba(148,163,184,.28); background:linear-gradient(145deg, rgba(38,49,66,.92), rgba(18,25,38,.88)); }
    .ai-plan-shell .ai-plan-list { margin:0; padding-left:1.1rem; }
    .ai-plan-shell .ai-plan-plan-block { border-top:1px solid rgba(148,163,184,.1); padding-top:1rem; }
    .ai-plan-shell .ai-plan-plan-intro { display:grid; gap:1rem; margin-bottom:1rem; }
    .ai-plan-shell .ai-plan-plan-panel { border:1px solid rgba(148,163,184,.12); border-radius:1.2rem; padding:1.05rem 1.1rem; background:linear-gradient(160deg, rgba(28,34,46,.9), rgba(17,22,32,.86)); box-shadow:0 .75rem 1.6rem rgba(2,6,23,.14), inset 0 1px 0 rgba(255,255,255,.025); }
    .ai-plan-shell .ai-plan-plan-panel.primary { border-color:rgba(71,85,105,.24); background:radial-gradient(360px 140px at 0% 0%, rgba(45,212,191,.06), transparent 64%), linear-gradient(160deg, rgba(22,33,39,.94), rgba(15,22,29,.9)); box-shadow:0 .85rem 1.8rem rgba(2,6,23,.16), inset 0 1px 0 rgba(255,255,255,.028); }
    .ai-plan-shell .ai-plan-plan-panel.truth { border-color:rgba(120,113,108,.24); background:radial-gradient(300px 120px at 100% 0%, rgba(245,158,11,.06), transparent 62%), linear-gradient(160deg, rgba(35,30,26,.9), rgba(22,21,25,.88)); }
    .ai-plan-shell .ai-plan-plan-panel.move { border-color:rgba(71,85,105,.24); background:radial-gradient(320px 120px at 0% 0%, rgba(56,189,248,.05), transparent 62%), linear-gradient(160deg, rgba(20,30,40,.92), rgba(15,21,30,.88)); }
    .ai-plan-shell .ai-plan-plan-label { display:inline-flex; align-items:center; align-self:flex-start; padding:.28rem .64rem; margin-bottom:.7rem; border-radius:999px; font-size:.68rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:rgba(226,232,240,.9); background:rgba(15,23,42,.44); border:1px solid rgba(148,163,184,.16); box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .ai-plan-shell .ai-plan-plan-panel.primary .ai-plan-plan-label { color:rgba(220,252,231,.92); background:rgba(20,37,35,.58); border-color:rgba(74,222,128,.14); }
    .ai-plan-shell .ai-plan-plan-panel.truth .ai-plan-plan-label { color:rgba(254,243,199,.92); background:rgba(55,37,20,.58); border-color:rgba(245,158,11,.16); }
    .ai-plan-shell .ai-plan-plan-panel.move .ai-plan-plan-label { color:rgba(224,242,254,.92); background:rgba(20,36,52,.58); border-color:rgba(96,165,250,.16); }
    .ai-plan-shell .ai-plan-plan-headline { font-size:1.15rem; line-height:1.5; color:#f8fafc; }
    .ai-plan-shell .ai-plan-plan-headline strong { display:block; margin-bottom:.45rem; font-size:1.95rem; line-height:1.08; letter-spacing:-.03em; color:#ffffff; }
    .ai-plan-shell .ai-plan-plan-copy { font-size:1.03rem; line-height:1.75; color:rgba(226,232,240,.95); }
    .ai-plan-shell .ai-plan-plan-copy.emphasis { font-size:1.08rem; font-weight:700; color:#67e8f9; }
    .ai-plan-shell .ai-plan-day-stream { position:relative; display:grid; gap:1rem; }
    .ai-plan-shell .ai-plan-day-stream::before { content:''; position:absolute; top:.6rem; bottom:.6rem; left:111px; width:2px; background:linear-gradient(180deg, rgba(14,165,233,.14), rgba(125,211,252,.55), rgba(14,165,233,.14)); }
    .ai-plan-shell .ai-plan-day-row { position:relative; display:grid; grid-template-columns:88px 30px minmax(0, 1fr); gap:1rem; align-items:stretch; }
    .ai-plan-shell .ai-plan-day-side { display:flex; flex-direction:column; justify-content:flex-start; align-items:flex-end; gap:.6rem; min-width:0; padding-top:.35rem; text-align:right; }
    .ai-plan-shell .ai-plan-day-marker { position:relative; display:flex; align-items:flex-start; justify-content:center; padding-top:.45rem; }
    .ai-plan-shell .ai-plan-day-node { position:relative; z-index:1; width:14px; height:14px; border-radius:999px; background:linear-gradient(180deg, #38bdf8, #0ea5e9); box-shadow:0 0 0 6px rgba(14,165,233,.12), 0 8px 18px rgba(14,165,233,.22); }
    .ai-plan-shell .ai-plan-day-card { border:1px solid rgba(148,163,184,.1); border-radius:1.15rem; padding:1rem 1.05rem; background:linear-gradient(160deg, rgba(29,35,46,.88), rgba(18,22,31,.86)); box-shadow:0 .65rem 1.4rem rgba(2,6,23,.12), inset 0 1px 0 rgba(255,255,255,.02); }
    .ai-plan-shell .ai-plan-day-badge { display:inline-flex; align-items:center; align-self:flex-start; padding:.28rem .62rem; border-radius:999px; font-size:.72rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#0369a1; background:rgba(125,211,252,.18); border:1px solid rgba(14,165,233,.18); }
    .ai-plan-shell .ai-plan-day-title { font-size:1.45rem; font-weight:800; line-height:1.08; letter-spacing:-.02em; margin:0; max-width:12ch; color:#f8fafc; }
    .ai-plan-shell .ai-plan-day-content { min-width:0; display:flex; align-items:center; }
    .ai-plan-shell .ai-plan-task-list { list-style:none; margin:0; padding:0; width:100%; display:grid; gap:.8rem; }
    .ai-plan-shell .ai-plan-task-item { position:relative; padding-left:1.15rem; font-size:.98rem; line-height:1.72; color:rgba(226,232,240,.92); }
    .ai-plan-shell .ai-plan-task-item::before { content:''; position:absolute; top:.74rem; left:0; width:.34rem; height:.34rem; border-radius:999px; background:rgba(56,189,248,.88); box-shadow:0 0 0 3px rgba(56,189,248,.08); }
    .ai-plan-shell .ai-plan-advice-stack { display:grid; gap:1rem; margin-top:1rem; }
    .ai-plan-shell .ai-plan-advice-row { display:grid; grid-template-columns:260px minmax(0, 1fr); gap:1.1rem; border:1px solid rgba(148,163,184,.1); border-radius:1.15rem; padding:1rem; background:linear-gradient(160deg, rgba(28,34,46,.88), rgba(17,22,32,.84)); box-shadow:0 .7rem 1.45rem rgba(2,6,23,.11), inset 0 1px 0 rgba(255,255,255,.02); }
    .ai-plan-shell .ai-plan-advice-side { display:flex; flex-direction:column; justify-content:space-between; gap:.75rem; padding:.2rem 0; }
    .ai-plan-shell .ai-plan-advice-kicker { font-size:.68rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:rgba(148,163,184,.82); }
    .ai-plan-shell .ai-plan-advice-title { font-size:1.28rem; font-weight:800; line-height:1.15; letter-spacing:-.02em; margin-bottom:.45rem; color:#f8fafc; }
    .ai-plan-shell .ai-plan-advice-note { font-size:.92rem; line-height:1.58; color:rgba(148,163,184,.84); max-width:22ch; }
    .ai-plan-shell .ai-plan-advice-copy { font-size:.98rem; line-height:1.68; display:flex; align-items:center; }
    .ai-plan-shell .ai-plan-advice-row.is-content .ai-plan-advice-kicker { color:rgba(191,219,254,.86); }
    .ai-plan-shell .ai-plan-advice-row.is-coach .ai-plan-advice-kicker { color:rgba(167,243,208,.86); }
    .ai-plan-shell .ai-plan-advice-row.is-boundary .ai-plan-advice-kicker { color:rgba(253,230,138,.86); }
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
    .ai-plan-shell .ai-plan-guide-card { position:relative; overflow:hidden; border:1px solid rgba(45,212,191,.18); background:radial-gradient(500px 180px at 0% 0%, rgba(45,212,191,.1), transparent 58%), linear-gradient(135deg, rgba(45,212,191,.08), rgba(14,165,233,.035)); box-shadow:0 1rem 2rem rgba(2,6,23,.16); }
    .ai-plan-shell .ai-plan-guide-card::before { content:''; position:absolute; inset:0; pointer-events:none; background:linear-gradient(120deg, rgba(255,255,255,.025), transparent 35%, transparent 70%, rgba(255,255,255,.02)); }
    .ai-plan-shell .ai-plan-step-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:1rem; }
    .ai-plan-shell .ai-plan-step-card { display:flex; flex-direction:column; gap:.72rem; padding:1.05rem 1.1rem; border:1px solid rgba(148,163,184,.12); border-radius:1.15rem; background:linear-gradient(145deg, rgba(255,255,255,.03), rgba(255,255,255,.015)); color:inherit; text-decoration:none; min-height:100%; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); transition:transform .2s ease, border-color .2s ease, box-shadow .2s ease, background .2s ease; }
    .ai-plan-shell .ai-plan-step-card:hover { color:inherit; text-decoration:none; border-color:rgba(14,165,233,.24); box-shadow:0 .9rem 1.8rem rgba(15,23,42,.12); transform:translateY(-2px); }
    .ai-plan-shell .ai-plan-step-card.current { border-color:rgba(45,212,191,.28); background:linear-gradient(145deg, rgba(45,212,191,.12), rgba(14,165,233,.06)); box-shadow:0 1rem 1.9rem rgba(15,23,42,.14); }
    .ai-plan-shell .ai-plan-step-card.done { border-color:rgba(34,197,94,.22); background:linear-gradient(145deg, rgba(34,197,94,.08), rgba(255,255,255,.015)); }
    .ai-plan-shell .ai-plan-step-card.locked { border-color:rgba(249,115,22,.22); background:linear-gradient(145deg, rgba(249,115,22,.06), rgba(255,255,255,.012)); }
    .ai-plan-shell .ai-plan-step-card.review-ready { border-color:rgba(56,189,248,.32); background:radial-gradient(340px 140px at 0% 0%, rgba(56,189,248,.14), transparent 62%), linear-gradient(145deg, rgba(8,145,178,.1), rgba(14,165,233,.05)); box-shadow:0 1rem 1.9rem rgba(8,47,73,.16); }
    .ai-plan-shell .ai-plan-step-card.review-waiting { border-color:rgba(250,204,21,.26); background:radial-gradient(320px 130px at 100% 0%, rgba(250,204,21,.12), transparent 60%), linear-gradient(145deg, rgba(250,204,21,.06), rgba(255,255,255,.014)); }
    .ai-plan-shell .ai-plan-step-status { display:inline-flex; align-items:center; align-self:flex-start; padding:.28rem .72rem; border-radius:999px; font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; border:1px solid rgba(148,163,184,.18); text-shadow:0 1px 0 rgba(255,255,255,.06); box-shadow:inset 0 1px 0 rgba(255,255,255,.06); }
    .ai-plan-shell .ai-plan-step-status.current { color:#d5fffb; background:linear-gradient(135deg, rgba(13,148,136,.45), rgba(15,118,110,.34)); border-color:rgba(45,212,191,.34); }
    .ai-plan-shell .ai-plan-step-status.done { color:#ecfdf5; background:linear-gradient(135deg, rgba(22,163,74,.42), rgba(21,128,61,.32)); border-color:rgba(74,222,128,.3); }
    .ai-plan-shell .ai-plan-step-status.locked { color:#fff1e6; background:linear-gradient(135deg, rgba(234,88,12,.4), rgba(180,83,9,.28)); border-color:rgba(251,146,60,.3); }
    .ai-plan-shell .ai-plan-step-status.next { color:#e0f2fe; background:linear-gradient(135deg, rgba(2,132,199,.42), rgba(3,105,161,.28)); border-color:rgba(56,189,248,.3); }
    .ai-plan-shell .ai-plan-step-status.review-ready { color:#e0f2fe; background:linear-gradient(135deg, rgba(3,105,161,.48), rgba(2,132,199,.32)); border-color:rgba(56,189,248,.34); }
    .ai-plan-shell .ai-plan-step-status.review-waiting { color:#fef3c7; background:linear-gradient(135deg, rgba(161,98,7,.44), rgba(202,138,4,.28)); border-color:rgba(250,204,21,.32); }
    .ai-plan-shell .ai-plan-step-card .font-weight-bold { font-size:1.12rem; line-height:1.3; color:#f8fafc; }
    .ai-plan-shell .ai-plan-step-card .text-muted { color:rgba(191,203,218,.84) !important; line-height:1.6; }
    .ai-plan-shell .ai-plan-step-meta { display:grid; gap:.45rem; margin-top:auto; padding-top:.15rem; }
    .ai-plan-shell .ai-plan-step-meta-row { display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; padding-top:.45rem; border-top:1px solid rgba(148,163,184,.1); }
    .ai-plan-shell .ai-plan-step-meta-label { font-size:.72rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:rgba(148,163,184,.88); }
    .ai-plan-shell .ai-plan-step-meta-value { font-size:.84rem; line-height:1.45; color:#f8fafc; text-align:right; font-weight:700; }
    .ai-plan-shell .ai-plan-step-meta-value.is-highlight { color:#67e8f9; }
    .ai-plan-shell .ai-plan-step-meta-value.is-waiting { color:#fde68a; }
    .ai-plan-shell .ai-plan-form-card { position:relative; overflow:hidden; border:1px solid rgba(255,255,255,.08); background:radial-gradient(640px 220px at 0% 0%, rgba(14,165,233,.08), transparent 58%), linear-gradient(160deg, rgba(255,255,255,.032), rgba(255,255,255,.012)); box-shadow:0 1.15rem 2.3rem rgba(2,6,23,.16), inset 0 1px 0 rgba(255,255,255,.03); }
    .ai-plan-shell .ai-plan-form-card::before { content:''; position:absolute; inset:0; pointer-events:none; background:linear-gradient(120deg, rgba(255,255,255,.02), transparent 28%, transparent 72%, rgba(255,255,255,.015)); }
    .ai-plan-shell .ai-plan-profile-card { border-color:rgba(93,167,255,.18); background:radial-gradient(circle at 12% 14%, rgba(63,215,199,.12) 0%, rgba(63,215,199,0) 34%), radial-gradient(circle at 88% 10%, rgba(84,124,255,.11) 0%, rgba(84,124,255,0) 30%), radial-gradient(circle at 72% 0%, rgba(226,188,116,.07) 0%, rgba(226,188,116,0) 22%), linear-gradient(160deg, rgba(20,31,48,.98), rgba(10,16,29,.995)); box-shadow:0 1.4rem 3rem rgba(2,8,23,.22), inset 0 3px 0 rgba(92,239,223,.78), inset 0 1px 0 rgba(255,255,255,.04); }
    .ai-plan-shell .ai-plan-profile-card::after { content:''; position:absolute; inset:auto auto -4.5rem -3rem; width:16rem; height:16rem; border-radius:999px; background:radial-gradient(circle, rgba(63,215,199,.1) 0%, rgba(63,215,199,0) 72%); pointer-events:none; }
    .ai-plan-shell .ai-plan-profile-card .card-body { position:relative; z-index:1; }
    .ai-plan-shell .ai-plan-profile-card .ai-plan-option input:checked + label { border-color:rgba(92,239,223,.34); background:linear-gradient(145deg, rgba(45,212,191,.16), rgba(14,165,233,.08)); box-shadow:0 1rem 1.8rem rgba(8,47,73,.12); }
    .ai-plan-shell .ai-plan-profile-card .btn-primary { box-shadow:0 1.1rem 2.3rem rgba(45,212,191,.2); }
    .ai-plan-shell .ai-plan-profile-card .btn-primary:hover { box-shadow:0 1.3rem 2.7rem rgba(45,212,191,.26); }
    .ai-plan-shell .ai-plan-weekly-card { border-color:rgba(93,167,255,.18); background:radial-gradient(circle at 12% 14%, rgba(63,215,199,.12) 0%, rgba(63,215,199,0) 34%), radial-gradient(circle at 88% 10%, rgba(84,124,255,.12) 0%, rgba(84,124,255,0) 30%), radial-gradient(circle at 72% 0%, rgba(226,188,116,.08) 0%, rgba(226,188,116,0) 22%), linear-gradient(160deg, rgba(20,31,48,.98), rgba(10,16,29,.995)); box-shadow:0 1.4rem 3rem rgba(2,8,23,.24), inset 0 3px 0 rgba(92,239,223,.78), inset 0 1px 0 rgba(255,255,255,.04); }
    .ai-plan-shell .ai-plan-weekly-card::after { content:''; position:absolute; inset:auto auto -4.5rem -3rem; width:16rem; height:16rem; border-radius:999px; background:radial-gradient(circle, rgba(63,215,199,.1) 0%, rgba(63,215,199,0) 72%); pointer-events:none; }
    .ai-plan-shell .ai-plan-weekly-card .card-body { position:relative; z-index:1; }
    .ai-plan-shell .ai-plan-weekly-card .ai-plan-cycle-panel { border-color:rgba(92,239,223,.2); background:radial-gradient(circle at top right, rgba(84,124,255,.1) 0%, rgba(84,124,255,0) 40%), linear-gradient(145deg, rgba(18,73,90,.26), rgba(15,23,42,.28)); box-shadow:0 1rem 2rem rgba(2,6,23,.12), inset 0 1px 0 rgba(255,255,255,.04); }
    .ai-plan-shell .ai-plan-weekly-card .ai-plan-cycle-card.current { border-color:rgba(92,239,223,.3); background:linear-gradient(145deg, rgba(45,212,191,.16), rgba(14,165,233,.08)); box-shadow:0 1rem 1.9rem rgba(8,47,73,.16); }
    .ai-plan-shell .ai-plan-weekly-card .ai-plan-cycle-next-value { color:#ecfeff; }
    .ai-plan-shell .ai-plan-weekly-card .btn-primary { box-shadow:0 1.1rem 2.3rem rgba(45,212,191,.2); }
    .ai-plan-shell .ai-plan-weekly-card .btn-primary:hover { box-shadow:0 1.3rem 2.7rem rgba(45,212,191,.26); }
    .ai-plan-shell .ai-plan-form-card .card-body,
    .ai-plan-shell .ai-plan-tool-card .card-body,
    .ai-plan-shell .ai-plan-side-card .card-body { position:relative; z-index:1; }
    .ai-plan-shell .ai-plan-form-card .card-body { padding:1.45rem 1.5rem 1.35rem; }
    .ai-plan-shell .ai-plan-tool-card .card-body,
    .ai-plan-shell .ai-plan-side-card .card-body { padding:1.2rem 1.2rem 1.15rem; }
    .ai-plan-shell .ai-plan-form-card h2,
    .ai-plan-shell .ai-plan-tool-card h2 { font-size:1.28rem; line-height:1.2; color:#f8fafc; margin-bottom:.55rem !important; }
    .ai-plan-shell .ai-plan-form-card > .card-body > .d-flex .text-muted,
    .ai-plan-shell .ai-plan-tool-card > .card-body > .d-flex .text-muted { max-width:58ch; color:rgba(191,203,218,.8) !important; line-height:1.55; font-size:.96rem; }
    .ai-plan-shell .ai-plan-form-card .form-group > label,
    .ai-plan-shell .ai-plan-tool-card .form-group > label { font-size:.97rem; letter-spacing:-.01em; margin-bottom:.7rem; color:#f8fafc; }
    .ai-plan-shell .ai-plan-form-card .form-row + .form-row,
    .ai-plan-shell .ai-plan-form-card .form-group + .form-group { margin-top:.1rem; }
    .ai-plan-shell .ai-plan-option label { padding:.86rem .92rem; border-radius:.95rem; border-color:rgba(148,163,184,.16); background:linear-gradient(145deg, rgba(255,255,255,.03), rgba(255,255,255,.012)); box-shadow:inset 0 1px 0 rgba(255,255,255,.03); font-size:.95rem; line-height:1.35; }
    .ai-plan-shell .ai-plan-option label:hover { border-color:rgba(125,211,252,.28); transform:translateY(-1px); box-shadow:0 .8rem 1.4rem rgba(15,23,42,.08); }
    .ai-plan-shell .ai-plan-option input:checked + label { border-color:rgba(45,212,191,.35); background:linear-gradient(145deg, rgba(45,212,191,.12), rgba(14,165,233,.06)); color:#f8fafc; box-shadow:0 1rem 1.8rem rgba(15,23,42,.12); }
    .ai-plan-shell .ai-plan-form-card .form-control,
    .ai-plan-shell .ai-plan-form-card .custom-select,
    .ai-plan-shell .ai-plan-tool-card .form-control,
    .ai-plan-shell .ai-plan-tool-card .custom-select { min-height:3.15rem; border-radius:.95rem; border-color:rgba(148,163,184,.16); background:linear-gradient(145deg, rgba(15,23,42,.54), rgba(15,23,42,.38)); color:#f8fafc; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); font-size:.95rem; }
    .ai-plan-shell .ai-plan-form-card textarea.form-control,
    .ai-plan-shell .ai-plan-tool-card textarea.form-control { min-height:6.4rem; }
    .ai-plan-shell .ai-plan-form-card .form-control::placeholder,
    .ai-plan-shell .ai-plan-tool-card .form-control::placeholder { color:rgba(148,163,184,.72); }
    .ai-plan-shell .ai-plan-form-card .custom-select:focus,
    .ai-plan-shell .ai-plan-form-card .form-control:focus,
    .ai-plan-shell .ai-plan-tool-card .custom-select:focus,
    .ai-plan-shell .ai-plan-tool-card .form-control:focus { border-color:rgba(45,212,191,.35); box-shadow:0 0 0 .18rem rgba(45,212,191,.08); }
    .ai-plan-shell .ai-plan-side-card { position:relative; overflow:hidden; border:1px solid rgba(255,255,255,.08); background:linear-gradient(160deg, rgba(255,255,255,.03), rgba(255,255,255,.012)); box-shadow:0 .95rem 1.9rem rgba(2,6,23,.14), inset 0 1px 0 rgba(255,255,255,.025); }
    .ai-plan-shell .ai-plan-side-card::before { content:''; position:absolute; inset:0; pointer-events:none; background:radial-gradient(320px 140px at 100% 0%, rgba(59,130,246,.08), transparent 60%); }
    .ai-plan-shell .ai-plan-side-card h2 { font-size:1.02rem; line-height:1.22; color:#f8fafc; margin-bottom:.7rem !important; }
    .ai-plan-shell .ai-plan-side-card .small.mb-3,
    .ai-plan-shell .ai-plan-side-card .text-muted.small.mb-0,
    .ai-plan-shell .ai-plan-side-card .text-muted.small { color:rgba(191,203,218,.82) !important; line-height:1.55; font-size:.88rem; }
    .ai-plan-shell .ai-plan-side-card .small.mb-0,
    .ai-plan-shell .ai-plan-side-card .small { font-size:.9rem; line-height:1.55; }
    .ai-plan-shell .ai-plan-side-card .ai-plan-stat-row { grid-template-columns:minmax(0, .92fr) minmax(0, 1.08fr); gap:1rem; }
    .ai-plan-shell .ai-plan-side-card .ai-plan-stat-row > span { color:rgba(148,163,184,.88); }
    .ai-plan-shell .ai-plan-side-card .ai-plan-stat-row > strong { color:#f8fafc; font-size:.96rem; line-height:1.32; overflow-wrap:break-word; word-break:normal; max-width:18ch; }
    .ai-plan-shell .ai-plan-side-card .ai-plan-history-item { padding:.72rem 0; }
    .ai-plan-shell .ai-plan-tool-card { position:relative; overflow:hidden; border:1px solid rgba(255,255,255,.08); background:radial-gradient(520px 180px at 0% 0%, rgba(14,165,233,.08), transparent 58%), linear-gradient(160deg, rgba(255,255,255,.032), rgba(255,255,255,.012)); box-shadow:0 1.05rem 2rem rgba(2,6,23,.15), inset 0 1px 0 rgba(255,255,255,.03); }
    .ai-plan-shell .ai-plan-tool-card::before { content:''; position:absolute; inset:0; pointer-events:none; background:linear-gradient(120deg, rgba(255,255,255,.02), transparent 36%, transparent 70%, rgba(255,255,255,.015)); }
    .ai-plan-shell .ai-plan-app-review-card { border-color:rgba(93,167,255,.18); background:radial-gradient(circle at 12% 14%, rgba(63,215,199,.12) 0%, rgba(63,215,199,0) 34%), radial-gradient(circle at 88% 10%, rgba(84,124,255,.12) 0%, rgba(84,124,255,0) 30%), radial-gradient(circle at 72% 0%, rgba(226,188,116,.08) 0%, rgba(226,188,116,0) 22%), linear-gradient(160deg, rgba(20,31,48,.98), rgba(10,16,29,.995)); box-shadow:0 1.4rem 3rem rgba(2,8,23,.24), inset 0 3px 0 rgba(92,239,223,.78), inset 0 1px 0 rgba(255,255,255,.04); }
    .ai-plan-shell .ai-plan-app-review-card::after { content:''; position:absolute; inset:auto auto -4.5rem -3rem; width:16rem; height:16rem; border-radius:999px; background:radial-gradient(circle, rgba(63,215,199,.1) 0%, rgba(63,215,199,0) 72%); pointer-events:none; }
    .ai-plan-shell .ai-plan-app-review-card .card-body { position:relative; z-index:1; }
    .ai-plan-shell .ai-plan-app-review-card .ai-plan-review-box.ai-plan-app-review-form-box { border-color:rgba(118,180,255,.16); background:radial-gradient(circle at top right, rgba(84,124,255,.1) 0%, rgba(84,124,255,0) 40%), linear-gradient(165deg, rgba(18,27,45,.9), rgba(12,18,32,.88)); box-shadow:0 1rem 2rem rgba(2,6,23,.12), inset 0 1px 0 rgba(255,255,255,.04); }
    .ai-plan-shell .ai-plan-app-review-card .ai-plan-lock-box.ai-plan-app-review-lock { border-color:rgba(118,180,255,.14); background:linear-gradient(160deg, rgba(24,31,47,.86), rgba(17,24,39,.82)); box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .ai-plan-shell .ai-plan-app-review-card .ai-plan-soft-box.ai-plan-app-review-main-app { border-color:rgba(118,180,255,.14); background:radial-gradient(circle at top right, rgba(84,124,255,.08) 0%, rgba(84,124,255,0) 38%), linear-gradient(160deg, rgba(20,29,46,.86), rgba(14,21,34,.82)); box-shadow:0 .9rem 1.8rem rgba(2,6,23,.08), inset 0 1px 0 rgba(255,255,255,.03); }
    .ai-plan-shell .ai-plan-app-review-card #ai-plan-app-review-submit { box-shadow:0 1.1rem 2.3rem rgba(45,212,191,.2); }
    .ai-plan-shell .ai-plan-app-review-card #ai-plan-app-review-submit:hover { box-shadow:0 1.3rem 2.7rem rgba(45,212,191,.26); }
    .ai-plan-shell .ai-plan-tool-header { display:grid; grid-template-columns:minmax(0, 1fr) auto; gap:1rem; align-items:start; margin-bottom:1rem; }
    .ai-plan-shell .ai-plan-tool-heading { max-width:54ch; }
    .ai-plan-shell .ai-plan-tool-heading .text-muted { color:rgba(191,203,218,.78) !important; line-height:1.52; font-size:.94rem; }
    .ai-plan-shell .ai-plan-tool-meta { display:flex; flex-direction:column; align-items:flex-end; gap:.55rem; }
    .ai-plan-shell .ai-plan-inline-meta { gap:.55rem; margin-bottom:1rem; padding:.1rem 0 .05rem; }
    .ai-plan-shell .ai-plan-inline-meta .ai-plan-chip { box-shadow:none; background:linear-gradient(145deg, rgba(20,31,53,.8), rgba(13,20,36,.78)); border-color:rgba(96,165,250,.24); color:#dbeafe; }
    .ai-plan-shell .ai-plan-review-grid { grid-template-columns:minmax(0, 1.05fr) minmax(320px, .95fr); gap:1.05rem; }
    .ai-plan-shell .ai-plan-review-box { display:flex; flex-direction:column; gap:1rem; padding:1.05rem; border-radius:1.2rem; border-color:rgba(148,163,184,.14); background:linear-gradient(165deg, rgba(18,25,42,.84), rgba(12,18,32,.82)); box-shadow:inset 0 1px 0 rgba(255,255,255,.025); }
    .ai-plan-shell .ai-plan-review-box .ai-plan-lock-box,
    .ai-plan-shell .ai-plan-review-box .ai-plan-soft-box { background:linear-gradient(155deg, rgba(255,255,255,.025), rgba(255,255,255,.012)); border-color:rgba(148,163,184,.16); }
    .ai-plan-shell .ai-plan-processing-box { display:none; border:1px solid rgba(45,212,191,.18); border-radius:1rem; padding:1rem 1.05rem; background:linear-gradient(145deg, rgba(45,212,191,.08), rgba(14,165,233,.04)); box-shadow:0 .9rem 1.7rem rgba(2,6,23,.08); }
    .ai-plan-shell .ai-plan-processing-box.is-visible { display:block; }
    .ai-plan-shell .ai-plan-processing-steps { display:grid; gap:.45rem; margin-top:.8rem; }
    .ai-plan-shell .ai-plan-processing-step { position:relative; padding-left:1rem; color:rgba(226,232,240,.9); font-size:.92rem; line-height:1.55; }
    .ai-plan-shell .ai-plan-processing-step::before { content:''; position:absolute; top:.55rem; left:0; width:.36rem; height:.36rem; border-radius:999px; background:#2dd4bf; box-shadow:0 0 0 4px rgba(45,212,191,.08); }
    .ai-plan-shell .ai-plan-preview-card { border-radius:1.2rem; border-color:rgba(148,163,184,.14); background:linear-gradient(165deg, rgba(16,24,40,.88), rgba(10,16,29,.86)); box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .ai-plan-shell .ai-plan-preview-header { padding:1rem 1.05rem .9rem; gap:.9rem; border-bottom:1px solid rgba(148,163,184,.1); background:linear-gradient(180deg, rgba(255,255,255,.024), rgba(255,255,255,0)); }
    .ai-plan-shell .ai-plan-preview-header .font-weight-bold { font-size:1.08rem; line-height:1.28; color:#f8fafc; }
    .ai-plan-shell .ai-plan-preview-header .btn { padding:.58rem .9rem; border-radius:.9rem; font-weight:700; }
    .ai-plan-shell .ai-plan-preview-meta { font-size:.82rem; line-height:1.45; color:rgba(148,163,184,.88); max-width:28ch; overflow-wrap:anywhere; }
    .ai-plan-shell .ai-plan-review-highlight { border-color:rgba(45,212,191,.18); border-radius:1.15rem; box-shadow:0 .9rem 1.7rem rgba(2,6,23,.1); }
    .ai-plan-shell .ai-plan-review-results { display:grid; gap:1rem; margin-top:1.1rem; }
    .ai-plan-shell .ai-plan-review-section-card { border:1px solid rgba(148,163,184,.12); border-radius:1.15rem; background:linear-gradient(155deg, rgba(255,255,255,.022), rgba(255,255,255,.01)); box-shadow:0 .9rem 1.7rem rgba(2,6,23,.08); overflow:hidden; }
    .ai-plan-shell .ai-plan-review-section-head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; padding:1rem 1.05rem .85rem; border-bottom:1px solid rgba(148,163,184,.08); background:linear-gradient(180deg, rgba(255,255,255,.022), rgba(255,255,255,0)); }
    .ai-plan-shell .ai-plan-review-section-head.compact { padding:.9rem 1.05rem .8rem; }
    .ai-plan-shell .ai-plan-review-section-kicker { display:inline-flex; align-items:center; padding:.22rem .58rem; border-radius:999px; font-size:.68rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#99f6e4; background:rgba(45,212,191,.09); border:1px solid rgba(45,212,191,.14); }
    .ai-plan-shell .ai-plan-review-section-title { margin:.55rem 0 0; font-size:1.02rem; line-height:1.25; letter-spacing:-.015em; color:#f8fafc; }
    .ai-plan-shell .ai-plan-review-section-text { margin:.45rem 0 0; font-size:.92rem; line-height:1.6; color:rgba(191,203,218,.84); max-width:60ch; }
    .ai-plan-shell .ai-plan-review-section-meta { text-align:right; font-size:.8rem; line-height:1.45; color:rgba(148,163,184,.84); }
    .ai-plan-shell .ai-plan-review-score { display:grid; grid-template-columns:170px minmax(0, 1fr); gap:1rem; padding:1rem 1.05rem; border:1px solid rgba(45,212,191,.16); border-radius:1.15rem; background:linear-gradient(145deg, rgba(45,212,191,.07), rgba(14,165,233,.035)); box-shadow:0 .9rem 1.7rem rgba(2,6,23,.08); }
    .ai-plan-shell .ai-plan-review-score-main { display:flex; flex-direction:column; justify-content:center; gap:.35rem; padding-right:.5rem; border-right:1px solid rgba(148,163,184,.12); }
    .ai-plan-shell .ai-plan-review-score-value { font-size:2.05rem; line-height:1; font-weight:800; letter-spacing:-.04em; color:#f8fafc; }
    .ai-plan-shell .ai-plan-review-score-label { display:inline-flex; align-items:center; align-self:flex-start; padding:.28rem .62rem; border-radius:999px; font-size:.72rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; border:1px solid rgba(148,163,184,.18); }
    .ai-plan-shell .ai-plan-review-score-label.strong { color:#dcfce7; background:rgba(34,197,94,.18); border-color:rgba(74,222,128,.24); }
    .ai-plan-shell .ai-plan-review-score-label.growing { color:#e0f2fe; background:rgba(14,165,233,.18); border-color:rgba(56,189,248,.24); }
    .ai-plan-shell .ai-plan-review-score-label.foundation { color:#ffedd5; background:rgba(249,115,22,.16); border-color:rgba(251,146,60,.24); }
    .ai-plan-shell .ai-plan-review-score-copy { color:rgba(226,232,240,.92); line-height:1.65; font-size:.94rem; }
    .ai-plan-shell .ai-plan-review-kpi-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.9rem; margin-top:1rem; }
    .ai-plan-shell .ai-plan-review-kpi { padding:1rem 1.05rem; border:1px solid rgba(148,163,184,.12); border-radius:1.05rem; background:linear-gradient(145deg, rgba(255,255,255,.025), rgba(255,255,255,.012)); min-height:100%; }
    .ai-plan-shell .ai-plan-review-kpi.hero { background:linear-gradient(145deg, rgba(45,212,191,.08), rgba(14,165,233,.04)); border-color:rgba(45,212,191,.18); box-shadow:0 .9rem 1.7rem rgba(2,6,23,.08); }
    .ai-plan-shell .ai-plan-review-kpi.full { grid-column:1 / -1; }
    .ai-plan-shell .ai-plan-review-kpi-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:rgba(148,163,184,.84); margin-bottom:.45rem; }
    .ai-plan-shell .ai-plan-review-kpi-value { color:#eaf2fb; font-size:.95rem; font-weight:700; line-height:1.65; white-space:normal; }
    .ai-plan-shell .ai-plan-review-action-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.9rem; margin-top:1rem; }
    .ai-plan-shell .ai-plan-review-action-card { padding:1rem 1.05rem; border:1px solid rgba(148,163,184,.12); border-radius:1.05rem; background:linear-gradient(145deg, rgba(255,255,255,.025), rgba(255,255,255,.012)); min-height:100%; }
    .ai-plan-shell .ai-plan-review-action-card.primary { background:linear-gradient(145deg, rgba(45,212,191,.12), rgba(14,165,233,.05)); border-color:rgba(45,212,191,.22); box-shadow:0 1rem 1.8rem rgba(2,6,23,.08); }
    .ai-plan-shell .ai-plan-review-action-card.secondary { background:linear-gradient(145deg, rgba(59,130,246,.08), rgba(255,255,255,.012)); border-color:rgba(96,165,250,.18); }
    .ai-plan-shell .ai-plan-review-action-card.safe { background:linear-gradient(145deg, rgba(249,115,22,.08), rgba(255,255,255,.012)); border-color:rgba(251,146,60,.18); }
    .ai-plan-shell .ai-plan-review-action-title { font-size:.76rem; text-transform:uppercase; letter-spacing:.06em; color:rgba(191,203,218,.82); margin-bottom:.55rem; font-weight:800; }
    .ai-plan-shell .ai-plan-review-action-copy { color:#f8fafc; font-size:.95rem; font-weight:700; line-height:1.58; }
    .ai-plan-shell .ai-plan-review-detail-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:1rem; margin-top:1rem; }
    .ai-plan-shell .ai-plan-review-detail-card { padding:1rem 1.05rem; border:1px solid rgba(148,163,184,.12); border-radius:1.05rem; background:linear-gradient(145deg, rgba(255,255,255,.025), rgba(255,255,255,.012)); }
    .ai-plan-shell .ai-plan-review-detail-card.full { grid-column:1 / -1; }
    .ai-plan-shell .ai-plan-review-detail-card h3 { font-size:.95rem; line-height:1.25; margin:0 0 .8rem; color:#f8fafc; }
    .ai-plan-shell .ai-plan-review-color-grid { display:grid; gap:.7rem; }
    .ai-plan-shell .ai-plan-review-color-item { display:grid; gap:.35rem; padding:.8rem .85rem; border:1px solid rgba(148,163,184,.1); border-radius:.95rem; background:rgba(15,23,42,.22); }
    .ai-plan-shell .ai-plan-review-color-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .ai-plan-shell .ai-plan-review-color-label { font-size:.72rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:rgba(148,163,184,.86); }
    .ai-plan-shell .ai-plan-review-color-copy { color:rgba(226,232,240,.92); font-size:.92rem; line-height:1.58; }
    .ai-plan-shell .ai-plan-review-color-swatch { width:1rem; height:1rem; flex:0 0 1rem; border-radius:999px; border:1px solid rgba(255,255,255,.2); box-shadow:0 0 0 4px rgba(255,255,255,.04); }
    .ai-plan-shell .ai-plan-review-comparison-list { display:grid; gap:.7rem; }
    .ai-plan-shell .ai-plan-review-comparison-row { display:grid; grid-template-columns:minmax(0, 1fr) auto; gap:.8rem; padding:.55rem 0; border-bottom:1px solid rgba(148,163,184,.08); }
    .ai-plan-shell .ai-plan-review-comparison-row:last-child { border-bottom:0; padding-bottom:0; }
    .ai-plan-shell .ai-plan-review-disclosure-stack { display:grid; gap:.9rem; margin-top:.4rem; }
    .ai-plan-shell .ai-plan-review-comparison-target { color:rgba(148,163,184,.88); font-size:.86rem; }
    .ai-plan-shell .ai-plan-review-peer-list { display:flex; flex-wrap:wrap; gap:.55rem; }
    .ai-plan-shell .ai-plan-review-peer { display:inline-flex; align-items:center; gap:.45rem; padding:.42rem .68rem; border-radius:999px; font-size:.82rem; background:rgba(14,165,233,.1); border:1px solid rgba(56,189,248,.18); color:#dbeafe; }
    .ai-plan-shell .ai-plan-review-list li { font-size:.93rem; line-height:1.62; color:rgba(226,232,240,.9); }
    .ai-plan-shell .ai-plan-review-order-item { font-size:.93rem; line-height:1.58; }
    .ai-plan-shell .ai-plan-review-disclosure summary { padding:.9rem 1rem; font-size:.95rem; }
    .ai-plan-shell .ai-plan-review-disclosure-body { padding:0 1rem .95rem; }
    .ai-plan-shell .ai-plan-history-strip { display:grid; gap:.75rem; margin-top:1rem; }
    .ai-plan-shell .ai-plan-history-strip-head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; }
    .ai-plan-shell .ai-plan-history-list { display:grid; gap:.7rem; }
    .ai-plan-shell .ai-plan-history-entry { display:grid; gap:.45rem; }
    .ai-plan-shell .ai-plan-history-card { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; padding:.95rem 1rem; border:1px solid rgba(148,163,184,.12); border-radius:1rem; background:linear-gradient(145deg, rgba(255,255,255,.022), rgba(255,255,255,.01)); color:inherit; transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
    .ai-plan-shell .ai-plan-history-card:hover { transform:translateY(-1px); border-color:rgba(45,212,191,.18); box-shadow:0 .85rem 1.5rem rgba(2,6,23,.08); }
    .ai-plan-shell .ai-plan-history-card.active { border-color:rgba(45,212,191,.22); background:linear-gradient(145deg, rgba(45,212,191,.08), rgba(14,165,233,.03)); box-shadow:0 .95rem 1.7rem rgba(2,6,23,.08); }
    .ai-plan-shell .ai-plan-history-entry.active .ai-plan-history-card { border-bottom-left-radius:.9rem; border-bottom-right-radius:.9rem; }
    .ai-plan-shell .ai-plan-history-expanded { margin:-.05rem 0 .25rem 1rem; padding-left:1rem; border-left:1px solid rgba(45,212,191,.22); }
    .ai-plan-shell .ai-plan-history-expanded .ai-plan-review-results { margin-top:.2rem; }
    .ai-plan-shell .ai-plan-history-expanded .ai-plan-review-section-card { background:linear-gradient(155deg, rgba(255,255,255,.02), rgba(255,255,255,.008)); }
    .ai-plan-shell .ai-plan-history-expanded.ai-plan-plan-expanded { display:grid; gap:1rem; margin-top:-.05rem; }
    .ai-plan-shell .ai-plan-history-expanded.ai-plan-plan-expanded .card { margin-bottom:0 !important; }
    .ai-plan-shell .ai-plan-history-copy { min-width:0; display:grid; gap:.3rem; }
    .ai-plan-shell .ai-plan-history-title { font-size:.95rem; line-height:1.35; font-weight:700; color:#f8fafc; }
    .ai-plan-shell .ai-plan-history-meta { font-size:.8rem; line-height:1.4; color:rgba(148,163,184,.84); }
    .ai-plan-shell .ai-plan-history-note { font-size:.88rem; line-height:1.55; color:rgba(191,203,218,.82); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .ai-plan-shell .ai-plan-history-side { flex:0 0 auto; display:grid; gap:.45rem; justify-items:end; text-align:right; }
    .ai-plan-shell .ai-plan-history-score { font-size:1.1rem; font-weight:800; line-height:1; color:#f8fafc; }
    .ai-plan-shell .ai-plan-history-empty { padding:1rem 1.05rem; border:1px dashed rgba(148,163,184,.18); border-radius:1rem; background:rgba(255,255,255,.015); color:rgba(191,203,218,.82); }
    .ai-plan-shell .ai-plan-history-action { min-width:108px; justify-content:center; border-radius:999px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
    .ai-plan-shell .ai-plan-history-action.btn-outline-primary { border-color:rgba(56,189,248,.28); background:rgba(15,23,42,.24); }
    .ai-plan-shell .ai-plan-history-card.active .ai-plan-history-action.btn-outline-primary { border-color:rgba(45,212,191,.26); background:rgba(45,212,191,.08); color:#d5fffb; }
    .ai-plan-shell .ai-plan-tool-teaser { position:relative; overflow:hidden; border:1px solid rgba(45,212,191,.16); background:radial-gradient(520px 220px at 0% 0%, rgba(45,212,191,.1), transparent 56%), radial-gradient(420px 240px at 100% 0%, rgba(59,130,246,.12), transparent 58%), linear-gradient(155deg, rgba(255,255,255,.032), rgba(255,255,255,.012)); box-shadow:0 1.2rem 2.4rem rgba(2,6,23,.18), inset 0 1px 0 rgba(255,255,255,.03); }
    .ai-plan-shell .ai-plan-tool-teaser::before { content:''; position:absolute; inset:0; pointer-events:none; background:linear-gradient(110deg, rgba(255,255,255,.025), transparent 34%, transparent 72%, rgba(255,255,255,.018)); }
    .ai-plan-shell .ai-plan-tool-teaser-body { position:relative; z-index:1; display:grid; grid-template-columns:minmax(0, 1.08fr) minmax(290px, .82fr); gap:1rem; align-items:start; }
    .ai-plan-shell .ai-plan-tool-teaser-copy { display:flex; flex-direction:column; gap:.8rem; padding-right:.35rem; }
    .ai-plan-shell .ai-plan-tool-teaser-kicker { display:inline-flex; align-items:center; align-self:flex-start; padding:.34rem .72rem; border-radius:999px; font-size:.73rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#d5fffb; border:1px solid rgba(45,212,191,.24); background:linear-gradient(135deg, rgba(13,148,136,.26), rgba(14,165,233,.16)); box-shadow:0 .65rem 1.4rem rgba(15,23,42,.12); }
    .ai-plan-shell .ai-plan-tool-teaser-copy h2 { font-size:1.55rem; line-height:1.04; letter-spacing:-.03em; margin-bottom:0 !important; max-width:10ch; }
    .ai-plan-shell .ai-plan-tool-teaser-copy .text-muted { max-width:42ch; color:rgba(191,203,218,.84) !important; line-height:1.58; font-size:.97rem; }
    .ai-plan-shell .ai-plan-tool-teaser-meta { display:flex; flex-wrap:wrap; gap:.55rem; }
    .ai-plan-shell .ai-plan-tool-teaser-summary { max-width:36ch; font-size:.97rem; line-height:1.62; color:rgba(226,232,240,.9); }
    .ai-plan-shell .ai-plan-tool-teaser-actions { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.65rem; max-width:34rem; }
    .ai-plan-shell .ai-plan-tool-teaser-action { width:100%; min-height:2.9rem; display:inline-flex; align-items:center; justify-content:center; gap:.55rem; padding:.72rem 1rem; border-radius:1rem; border:1px solid rgba(148,163,184,.22); background:linear-gradient(145deg, rgba(19,28,41,.92), rgba(12,18,29,.86)); color:#e2e8f0; font-size:.94rem; font-weight:800; letter-spacing:-.01em; line-height:1.15; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); transition:transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease; }
    .ai-plan-shell .ai-plan-tool-teaser-action:hover:not(:disabled) { transform:translateY(-1px); border-color:rgba(45,212,191,.3); background:linear-gradient(145deg, rgba(24,35,51,.94), rgba(13,20,33,.9)); box-shadow:0 .9rem 1.7rem rgba(2,6,23,.12), inset 0 1px 0 rgba(255,255,255,.035); }
    .ai-plan-shell .ai-plan-tool-teaser-action.is-wide { grid-column:1 / -1; }
    .ai-plan-shell .ai-plan-tool-teaser-action.is-primary { border-color:rgba(45,212,191,.22); background:linear-gradient(145deg, rgba(45,212,191,.16), rgba(14,165,233,.08)); color:#d8fffb; }
    .ai-plan-shell .ai-plan-tool-teaser-action.is-primary:hover:not(:disabled) { border-color:rgba(45,212,191,.36); background:linear-gradient(145deg, rgba(45,212,191,.2), rgba(14,165,233,.1)); }
    .ai-plan-shell .ai-plan-tool-teaser-action.is-muted { color:rgba(203,213,225,.9); border-color:rgba(148,163,184,.18); }
    .ai-plan-shell .ai-plan-tool-teaser-action:disabled,
    .ai-plan-shell .ai-plan-tool-teaser-action.is-disabled { cursor:not-allowed; opacity:.5; filter:saturate(.85); box-shadow:none; transform:none !important; }
    .ai-plan-shell .ai-plan-tool-teaser-notification { margin-top:.75rem; }
    .ai-plan-shell .ai-plan-tool-teaser-notice { padding:.72rem .88rem; border-radius:.95rem; font-size:.88rem; line-height:1.5; border:1px solid rgba(148,163,184,.18); background:rgba(15,23,42,.28); color:#e2e8f0; }
    .ai-plan-shell .ai-plan-tool-teaser-notice.is-success { border-color:rgba(45,212,191,.24); background:rgba(13,148,136,.12); color:#ccfbf1; }
    .ai-plan-shell .ai-plan-tool-teaser-notice.is-warning { border-color:rgba(250,204,21,.24); background:rgba(133,77,14,.22); color:#fde68a; }
    .ai-plan-shell .ai-plan-tool-teaser-notice.is-error { border-color:rgba(248,113,113,.22); background:rgba(127,29,29,.24); color:#fecaca; }
    .ai-plan-shell .ai-plan-tool-teaser-side { display:flex; flex-direction:column; justify-content:flex-start; gap:.9rem; min-width:0; padding:1rem 1rem 1.05rem; border:1px solid rgba(96,165,250,.14); border-radius:1.25rem; background:radial-gradient(280px 180px at 0% 0%, rgba(14,165,233,.1), transparent 62%), linear-gradient(165deg, rgba(16,24,40,.92), rgba(12,18,32,.86)); box-shadow:inset 0 1px 0 rgba(255,255,255,.035), 0 1rem 2rem rgba(2,6,23,.14); }
    .ai-plan-shell .ai-plan-tool-teaser-score { display:grid; grid-template-columns:auto minmax(0, 1fr); gap:.75rem; align-items:center; }
    .ai-plan-shell .ai-plan-tool-teaser-score-value { font-size:3.35rem; line-height:.88; font-weight:800; letter-spacing:-.07em; color:#f8fafc; text-shadow:0 0 30px rgba(255,255,255,.06); }
    .ai-plan-shell .ai-plan-tool-teaser-score-copy { display:grid; gap:.25rem; min-width:0; }
    .ai-plan-shell .ai-plan-tool-teaser-score-label { font-size:.82rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:rgba(148,163,184,.86); }
    .ai-plan-shell .ai-plan-tool-teaser-score-level { font-size:1.02rem; font-weight:800; line-height:1.15; color:#f8fafc; }
    .ai-plan-shell .ai-plan-tool-teaser-cta { display:grid; gap:.6rem; }
    .ai-plan-shell .ai-plan-tool-teaser-cta .btn { width:100%; display:flex; justify-content:center; align-items:center; text-align:center; min-height:3rem; padding:.78rem 1rem; line-height:1.12; border-radius:1rem; font-weight:800; font-size:.94rem; letter-spacing:-.01em; border-width:2px; box-shadow:0 .9rem 1.8rem rgba(45,212,191,.12), inset 0 1px 0 rgba(255,255,255,.03); }
    .ai-plan-shell .ai-plan-tool-teaser-cta .btn:hover { transform:translateY(-1px); box-shadow:0 1.2rem 2.4rem rgba(45,212,191,.18), inset 0 1px 0 rgba(255,255,255,.04); }
    .ai-plan-shell .ai-plan-tool-teaser-reason { font-size:.9rem; line-height:1.55; color:rgba(203,213,225,.84); }
    .ai-plan-shell .ai-plan-tool-teaser-points { display:grid; gap:.42rem; margin:0; padding:0; list-style:none; }
    .ai-plan-shell .ai-plan-tool-teaser-points li { position:relative; padding-left:.95rem; font-size:.86rem; line-height:1.45; color:rgba(191,203,218,.8); }
    .ai-plan-shell .ai-plan-tool-teaser-points li::before { content:''; position:absolute; top:.6rem; left:0; width:.36rem; height:.36rem; border-radius:999px; background:#2dd4bf; box-shadow:0 0 0 4px rgba(45,212,191,.08); }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .card-body { padding:1rem 1rem 1.05rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-body { grid-template-columns:minmax(0, 1fr) minmax(245px, .72fr); gap:.85rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-copy { gap:.6rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-kicker { padding:.26rem .62rem; font-size:.68rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-copy h2 { font-size:1.15rem; line-height:1.08; max-width:11ch; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-copy .text-muted { font-size:.88rem; line-height:1.5; max-width:34ch; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-summary { font-size:.88rem; line-height:1.52; max-width:34ch; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-side { padding:.8rem .85rem .9rem; gap:.7rem; border-radius:1rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-score { gap:.6rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-score-value { font-size:2.45rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-score-label { font-size:.72rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-score-level { font-size:.9rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-cta .btn { min-height:2.65rem; padding:.68rem .85rem; font-size:.88rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-reason { font-size:.82rem; line-height:1.45; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-points { gap:.32rem; }
    .ai-plan-shell .ai-plan-tool-teaser.is-compact .ai-plan-tool-teaser-points li { font-size:.8rem; line-height:1.34; padding-left:.8rem; }
    .ai-plan-shell .ai-plan-cycle-panel { display:grid; gap:.9rem; padding:1rem 1.05rem; border:1px solid rgba(45,212,191,.14); border-radius:1.1rem; background:linear-gradient(145deg, rgba(45,212,191,.05), rgba(14,165,233,.025)); box-shadow:0 .8rem 1.6rem rgba(2,6,23,.08); }
    .ai-plan-shell .ai-plan-cycle-head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; }
    .ai-plan-shell .ai-plan-cycle-title { font-size:1rem; font-weight:800; color:#f8fafc; margin:0 0 .2rem; }
    .ai-plan-shell .ai-plan-cycle-text { margin:0; font-size:.9rem; line-height:1.58; color:rgba(191,203,218,.82); max-width:58ch; }
    .ai-plan-shell .ai-plan-cycle-next { align-self:flex-start; display:grid; gap:.15rem; min-width:180px; }
    .ai-plan-shell .ai-plan-cycle-next-label { font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:rgba(148,163,184,.84); }
    .ai-plan-shell .ai-plan-cycle-next-value { font-size:.92rem; font-weight:700; line-height:1.45; color:#d5fffb; }
    .ai-plan-shell .ai-plan-cycle-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.75rem; }
    .ai-plan-shell .ai-plan-cycle-card { display:grid; gap:.45rem; padding:.85rem .9rem; border:1px solid rgba(148,163,184,.12); border-radius:1rem; background:linear-gradient(145deg, rgba(255,255,255,.022), rgba(255,255,255,.01)); min-height:100%; }
    .ai-plan-shell .ai-plan-cycle-card.current { border-color:rgba(45,212,191,.22); background:linear-gradient(145deg, rgba(45,212,191,.1), rgba(14,165,233,.04)); box-shadow:0 .9rem 1.7rem rgba(2,6,23,.08); }
    .ai-plan-shell .ai-plan-cycle-card.done { border-color:rgba(74,222,128,.16); background:linear-gradient(145deg, rgba(34,197,94,.08), rgba(16,185,129,.03)); }
    .ai-plan-shell .ai-plan-cycle-card.locked { opacity:.85; }
    .ai-plan-shell .ai-plan-cycle-step { font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:rgba(148,163,184,.84); }
    .ai-plan-shell .ai-plan-cycle-card.current .ai-plan-cycle-step { color:#99f6e4; }
    .ai-plan-shell .ai-plan-cycle-card.done .ai-plan-cycle-step { color:#bbf7d0; }
    .ai-plan-shell .ai-plan-cycle-card-title { font-size:.95rem; font-weight:700; line-height:1.3; color:#f8fafc; }
    .ai-plan-shell .ai-plan-cycle-card-text { font-size:.84rem; line-height:1.5; color:rgba(191,203,218,.8); }
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
        .ai-plan-shell .ai-plan-tool-teaser-body { grid-template-columns:1fr; }
        .ai-plan-shell .ai-plan-tool-teaser-copy { padding-right:0; }
        .ai-plan-shell .ai-plan-tool-teaser-copy h2 { max-width:none; font-size:1.45rem; }
        .ai-plan-shell .ai-plan-tool-teaser-actions { grid-template-columns:1fr; max-width:none; }
        .ai-plan-shell .ai-plan-tool-teaser-action.is-wide { grid-column:auto; }
        .ai-plan-shell .ai-plan-tool-teaser-score-value { font-size:2.8rem; }
        .ai-plan-shell .ai-plan-cycle-grid { grid-template-columns:1fr; }
        .ai-plan-shell .ai-plan-cycle-next { min-width:0; }
        .ai-plan-shell .ai-plan-review-highlight-grid { grid-template-columns:1fr; }
        .ai-plan-shell .ai-plan-review-score,
        .ai-plan-shell .ai-plan-review-detail-grid,
        .ai-plan-shell .ai-plan-review-kpi-grid,
        .ai-plan-shell .ai-plan-review-action-grid { grid-template-columns:1fr; }
        .ai-plan-shell .ai-plan-review-score-main { border-right:0; padding-right:0; border-bottom:1px solid rgba(148,163,184,.12); padding-bottom:.85rem; }
        .ai-plan-shell .ai-plan-review-kpi.full,
        .ai-plan-shell .ai-plan-review-detail-card.full { grid-column:auto; }
        .ai-plan-shell .ai-plan-preview-frame-wrap,
        .ai-plan-shell .ai-plan-preview-frame,
        .ai-plan-shell .ai-plan-preview-empty { min-height:420px; }
        .ai-plan-shell .ai-plan-day-stream::before { display:none; }
        .ai-plan-shell .ai-plan-day-side { align-items:flex-start; text-align:left; padding-top:0; }
        .ai-plan-shell .ai-plan-day-marker { display:none; }
        .ai-plan-shell .ai-plan-day-title { max-width:none; font-size:1.2rem; }
        .ai-plan-shell .ai-plan-advice-note { max-width:none; }
        .ai-plan-shell .ai-plan-sticky-column { position:static; top:auto; }
        .ai-plan-shell .ai-plan-hero .h4 { max-width:none; font-size:1.55rem; white-space:normal; }
        .ai-plan-shell .ai-plan-hero p.text-muted { max-width:none; }
        .ai-plan-shell .ai-plan-stat-row { grid-template-columns:minmax(0, 1fr) auto; }
        .ai-plan-shell .ai-plan-side-card .ai-plan-stat-row > strong { max-width:none; }
        .ai-plan-shell .ai-plan-tool-header { grid-template-columns:1fr; }
        .ai-plan-shell .ai-plan-tool-meta { align-items:flex-start; }
        .ai-plan-shell .ai-plan-page-hero { grid-template-columns:1fr; }
        .ai-plan-shell .ai-plan-page-hero-title { font-size:1.8rem; }
        .ai-plan-shell .ai-plan-page-hero-action { width:100%; }
        .ai-plan-shell .ai-plan-page-hero-action .btn { width:100%; justify-content:center; }
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
<?php $app_review_page_url = (string) ($data->app_review_page_url ?? url('ai-plan?section=app_review')); ?>
<?php $app_review_is_accessible = (bool) ($data->app_review_is_accessible ?? false); ?>
<?php $app_review_locked_reason = (string) ($data->app_review_locked_reason ?? l('ai_plan.app_review_locked_entry_tooltip')); ?>
<?php $app_review_job_status = (array) ($data->app_review_job_status ?? []); ?>
<?php $app_review_is_processing = (string) ($app_review_job_status['status'] ?? '') === 'pending'; ?>
<?php $app_review_editor_actions = (array) ($data->app_review_editor_actions ?? []); ?>
<?php $render_app_review_link = static function(string $url, string $label, bool $is_accessible, string $locked_reason, string $class = 'btn btn-outline-primary'): string {
    $safe_label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $safe_reason = htmlspecialchars($locked_reason, ENT_QUOTES, 'UTF-8');

    if($is_accessible) {
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">' . $safe_label . '</a>';
    }

    return '<a href="#" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . ' disabled pointer-events-all ai-plan-disabled-link" data-tooltip title="' . $safe_reason . '" onclick="event.preventDefault();">' . $safe_label . '</a>';
}; ?>
<?php $app_review_color_palette_fields = static function(): array {
    return [
        'background' => 'ai_plan.app_review_color_palette.background',
        'heading' => 'ai_plan.app_review_color_palette.heading',
        'text' => 'ai_plan.app_review_color_palette.text',
        'primary_block_text' => 'ai_plan.app_review_color_palette.primary_block_text',
        'primary_block_background' => 'ai_plan.app_review_color_palette.primary_block_background',
        'primary_block_border' => 'ai_plan.app_review_color_palette.primary_block_border',
        'primary_block_shadow' => 'ai_plan.app_review_color_palette.primary_block_shadow',
        'secondary_blocks_text' => 'ai_plan.app_review_color_palette.secondary_blocks_text',
        'secondary_blocks_background' => 'ai_plan.app_review_color_palette.secondary_blocks_background',
        'secondary_blocks_border' => 'ai_plan.app_review_color_palette.secondary_blocks_border',
        'secondary_blocks_shadow' => 'ai_plan.app_review_color_palette.secondary_blocks_shadow',
    ];
}; ?>
<?php $extract_app_review_color_hex = static function(string $value): string {
    if(preg_match('/#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})\b/', $value, $matches)) {
        return $matches[0];
    }

    return '';
}; ?>
<?php $app_review_color_palette_has_content = static function(array $palette) use ($app_review_color_palette_fields): bool {
    foreach(array_keys($app_review_color_palette_fields()) as $field_key) {
        if(trim((string) ($palette[$field_key] ?? '')) !== '') {
            return true;
        }
    }

    foreach((array) ($palette['legacy_list'] ?? []) as $item) {
        if(trim((string) $item) !== '') {
            return true;
        }
    }

    return false;
}; ?>
<?php $render_app_review_color_palette = static function(array $palette) use ($app_review_color_palette_fields, $extract_app_review_color_hex): string {
    $fields = $app_review_color_palette_fields();
    $has_structured_values = false;

    foreach(array_keys($fields) as $field_key) {
        if(trim((string) ($palette[$field_key] ?? '')) !== '') {
            $has_structured_values = true;
            break;
        }
    }

    ob_start();

    if($has_structured_values):
    ?>
        <div class="ai-plan-review-color-grid">
            <?php foreach($fields as $field_key => $label_key): ?>
                <?php $copy = trim((string) ($palette[$field_key] ?? '')); ?>
                <?php if($copy === '') continue; ?>
                <?php $hex = $extract_app_review_color_hex($copy); ?>
                <div class="ai-plan-review-color-item">
                    <div class="ai-plan-review-color-head">
                        <span class="ai-plan-review-color-label"><?= l($label_key) ?></span>
                        <?php if($hex !== ''): ?>
                            <span class="ai-plan-review-color-swatch" style="background: <?= htmlspecialchars($hex, ENT_QUOTES, 'UTF-8') ?>;" title="<?= htmlspecialchars($hex, ENT_QUOTES, 'UTF-8') ?>"></span>
                        <?php endif ?>
                    </div>
                    <div class="ai-plan-review-color-copy"><?= htmlspecialchars($copy, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endforeach ?>
        </div>
    <?php
    else:
    ?>
        <ul class="ai-plan-review-list mb-0">
            <?php foreach((array) ($palette['legacy_list'] ?? []) as $item): ?>
                <?php $item = trim((string) $item); ?>
                <?php if($item === '') continue; ?>
                <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach ?>
        </ul>
    <?php
    endif;

    return (string) ob_get_clean();
}; ?>
<?php $app_review_theme_pack_has_content = static function(array $theme_pack): bool {
    return (bool) array_filter([
        $theme_pack['background_color'] ?? '',
        $theme_pack['gradient_start'] ?? '',
        $theme_pack['gradient_end'] ?? '',
        $theme_pack['primary_block_background'] ?? '',
        $theme_pack['secondary_blocks_background'] ?? '',
    ]);
}; ?>
<?php $app_review_evolution_display = is_array($data->app_review_evolution_payload ?? null) ? $data->app_review_evolution_payload : []; ?>
<?php $app_review_block_attribution = is_array($data->app_review_block_attribution_payload ?? null) ? $data->app_review_block_attribution_payload : []; ?>
<?php $render_app_review_evolution_status = static function(string $status): string {
    if($status === 'measured') {
        return l('link.settings.ai_evolution_status_measured');
    }

    if($status === 'ready') {
        return l('link.settings.ai_evolution_status_ready');
    }

    return l('link.settings.ai_evolution_status_pending');
}; ?>
<?php $render_app_review_result_cards = static function(array $review, array $quality_payload) use ($app_review_color_palette_has_content, $render_app_review_color_palette, $app_review_theme_pack_has_content, $app_review_evolution_display, $app_review_block_attribution, $render_app_review_evolution_status): string {
    $color_palette = is_array($review['color_palette'] ?? null) ? $review['color_palette'] : [];
    $has_color_palette = $app_review_color_palette_has_content($color_palette);
    $theme_pack = is_array($review['theme_pack'] ?? null) ? $review['theme_pack'] : [];
    $has_theme_pack = $app_review_theme_pack_has_content($theme_pack);
    $primary_block_plan = is_array($review['primary_block_plan'] ?? null) ? $review['primary_block_plan'] : [];
    $has_primary_block_plan = (bool) array_filter([
        $primary_block_plan['block_id'] ?? 0,
        $primary_block_plan['block_type'] ?? '',
        $primary_block_plan['label'] ?? '',
        $primary_block_plan['reason'] ?? '',
    ]);
    $copy_suggestions = is_array($review['copy_suggestions'] ?? null) ? $review['copy_suggestions'] : [];
    $layout_actions = is_array($review['layout_actions'] ?? null) ? $review['layout_actions'] : [];
    $signal_protection_summary = is_array($review['signal_protection_summary'] ?? null) ? $review['signal_protection_summary'] : [];
    $evolution_active_cycle = is_array($app_review_evolution_display['active_cycle'] ?? null) ? $app_review_evolution_display['active_cycle'] : [];
    ob_start();
    ?>
    <div class="ai-plan-review-results">
        <div class="ai-plan-review-section-card">
            <div class="ai-plan-review-section-head">
                <div>
                    <span class="ai-plan-review-section-kicker"><?= l('ai_plan.app_review_badge') ?></span>
                    <h3 class="ai-plan-review-section-title"><?= htmlspecialchars((string) (($review['headline'] ?? '') ?: l('ai_plan.app_review_page_title')), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="ai-plan-review-section-text"><?= htmlspecialchars((string) (($review['summary'] ?? '') ?: ($quality_payload['summary'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="ai-plan-review-section-meta">
                    <?php if(!empty($review['selected_app_name'])): ?>
                        <div class="font-weight-bold text-white mb-1"><?= htmlspecialchars((string) $review['selected_app_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif ?>
                    <?php if(!empty($review['generated_at'])): ?>
                        <div><?= l('ai_plan.plan_generated_at') ?>: <?= \Altum\Date::get($review['generated_at'], 2) ?></div>
                    <?php endif ?>
                </div>
            </div>

            <div class="p-3 p-lg-4">
                <div class="ai-plan-review-score">
                    <div class="ai-plan-review-score-main">
                        <div class="small text-muted"><?= l('ai_plan.app_review_quality_title') ?></div>
                        <div class="ai-plan-review-score-value"><?= nr((int) ($quality_payload['score'] ?? 0)) ?></div>
                        <span class="ai-plan-review-score-label <?= htmlspecialchars((string) ($quality_payload['level_key'] ?? 'foundation'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($quality_payload['level_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="ai-plan-review-score-copy">
                        <div class="font-weight-bold mb-2"><?= l('ai_plan.app_review_quality_subtitle') ?></div>
                        <div class="mb-3"><?= htmlspecialchars((string) ($quality_payload['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if(!empty($quality_payload['peer_examples'])): ?>
                            <div class="small text-muted mb-2"><?= l('ai_plan.app_review_quality_examples') ?></div>
                            <div class="ai-plan-review-peer-list">
                                <?php foreach(($quality_payload['peer_examples'] ?? []) as $peer): ?>
                                    <?php $peer_public_url = (string) ($peer['public_url'] ?? ''); ?>
                                    <a
                                        href="<?= htmlspecialchars($peer_public_url ?: '#', ENT_QUOTES, 'UTF-8') ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="ai-plan-review-peer border-0 js-app-review-example<?= $peer_public_url === '' ? ' disabled pointer-events-none opacity-60' : '' ?>"
                                        <?= $peer_public_url === '' ? 'onclick="event.preventDefault();"' : '' ?>
                                    >
                                        <span><?= htmlspecialchars((string) ($peer['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= nr((int) ($peer['weighted_signal_score'] ?? 0)) ?></strong>
                                    </a>
                                <?php endforeach ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>

                <div class="ai-plan-review-action-grid">
                    <div class="ai-plan-review-action-card primary">
                        <div class="ai-plan-review-action-title"><?= l('ai_plan.app_review_first_move') ?></div>
                        <div class="ai-plan-review-action-copy"><?= htmlspecialchars((string) (($review['first_move'] ?? '') ?: ($review['top_recommendation'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="ai-plan-review-action-card secondary">
                        <div class="ai-plan-review-action-title"><?= l('ai_plan.app_review_next_move') ?></div>
                        <div class="ai-plan-review-action-copy"><?= htmlspecialchars((string) (($review['next_move'] ?? '') ?: ($review['weekly_focus'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="ai-plan-review-action-card safe">
                        <div class="ai-plan-review-action-title"><?= l('ai_plan.app_review_do_not_touch') ?></div>
                        <div class="ai-plan-review-action-copy"><?= htmlspecialchars((string) (($review['do_not_touch'] ?? '') ?: (($review['keep_doing'][0] ?? '') ?: '-')), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>

                <div class="ai-plan-review-kpi-grid">
                    <div class="ai-plan-review-kpi hero">
                        <div class="ai-plan-review-kpi-label"><?= l('ai_plan.app_review_bottleneck') ?></div>
                        <div class="ai-plan-review-kpi-value"><?= htmlspecialchars((string) ($review['biggest_bottleneck'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="ai-plan-review-kpi hero">
                        <div class="ai-plan-review-kpi-label"><?= l('ai_plan.app_review_top_recommendation') ?></div>
                        <div class="ai-plan-review-kpi-value"><?= htmlspecialchars((string) ($review['top_recommendation'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="ai-plan-review-kpi hero">
                        <div class="ai-plan-review-kpi-label"><?= l('ai_plan.app_review_weekly_focus') ?></div>
                        <div class="ai-plan-review-kpi-value"><?= htmlspecialchars((string) (($review['weekly_focus'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="ai-plan-review-section-card">
            <div class="ai-plan-review-section-head compact">
                <div>
                    <span class="ai-plan-review-section-kicker"><?= l('ai_plan.app_review_top_recommendation') ?></span>
                    <h3 class="ai-plan-review-section-title"><?= l('ai_plan.app_review_priority_actions') ?></h3>
                    <p class="ai-plan-review-section-text"><?= l('ai_plan.app_review_footer') ?></p>
                </div>
            </div>
            <div class="p-3 p-lg-4">
                <div class="ai-plan-review-disclosure-stack" data-accordion-group="app-review-details">
                    <details class="ai-plan-review-disclosure" data-accordion-item="app-review-details" open>
                        <summary><?= l('ai_plan.app_review_priority_actions') ?></summary>
                        <div class="ai-plan-review-disclosure-body">
                            <ul class="ai-plan-review-list mb-0">
                                <?php foreach(($review['priority_actions'] ?? []) as $action): ?>
                                    <li><?= htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    </details>

                    <details class="ai-plan-review-disclosure" data-accordion-item="app-review-details">
                        <summary><?= l('ai_plan.app_review_block_order') ?></summary>
                        <div class="ai-plan-review-disclosure-body">
                            <div class="ai-plan-review-order mb-0">
                                <?php foreach(($review['ideal_block_order'] ?? []) as $index => $item): ?>
                                    <div class="ai-plan-review-order-item"><span class="ai-plan-review-order-step"><?= $index + 1 ?></span><div><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></div></div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </details>

                    <?php if(!empty($review['funnel_blueprint']) || $has_color_palette || !empty($review['trust_builders'])): ?>
                        <details class="ai-plan-review-disclosure" data-accordion-item="app-review-details">
                            <summary><?= l('ai_plan.app_review_funnel_blueprint') ?> &amp; <?= l('ai_plan.app_review_color_palette') ?></summary>
                            <div class="ai-plan-review-disclosure-body">
                                <div class="ai-plan-review-detail-grid" style="margin-top:0;">
                                    <?php if(!empty($review['funnel_blueprint'])): ?>
                                        <div class="ai-plan-review-detail-card">
                                            <h3><?= l('ai_plan.app_review_funnel_blueprint') ?></h3>
                                            <ul class="ai-plan-review-list mb-0">
                                                <?php foreach(($review['funnel_blueprint'] ?? []) as $item): ?>
                                                    <li><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>

                                    <?php if($has_color_palette): ?>
                                        <div class="ai-plan-review-detail-card full">
                                            <h3><?= l('ai_plan.app_review_color_palette') ?></h3>
                                            <?= $render_app_review_color_palette($color_palette) ?>
                                        </div>
                                    <?php endif ?>

                                    <?php if(!empty($review['trust_builders'])): ?>
                                        <div class="ai-plan-review-detail-card full">
                                            <h3><?= l('ai_plan.app_review_trust_builders') ?></h3>
                                            <ul class="ai-plan-review-list mb-0">
                                                <?php foreach(($review['trust_builders'] ?? []) as $item): ?>
                                                    <li><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </details>
                    <?php endif ?>

                    <?php if($has_theme_pack || $has_primary_block_plan || !empty($copy_suggestions) || !empty($layout_actions)): ?>
                        <details class="ai-plan-review-disclosure" data-accordion-item="app-review-details">
                            <summary><?= l('ai_plan.app_review_editor_ready') ?></summary>
                            <div class="ai-plan-review-disclosure-body">
                                <div class="ai-plan-review-detail-grid" style="margin-top:0;">
                                    <?php if($has_theme_pack): ?>
                                        <div class="ai-plan-review-detail-card">
                                            <h3><?= l('ai_plan.app_review_editor_theme_ready') ?></h3>
                                            <p class="mb-3"><?= htmlspecialchars((string) (($theme_pack['summary'] ?? '') ?: l('ai_plan.app_review_editor_theme_ready_text')), ENT_QUOTES, 'UTF-8') ?></p>
                                            <ul class="ai-plan-review-list mb-0">
                                                <li><?= l('ai_plan.app_review_editor_location') ?></li>
                                                <?php if(!empty($theme_pack['background_mode']) && (($theme_pack['background_color'] ?? '') !== '' || ($theme_pack['gradient_start'] ?? '') !== '')): ?>
                                                    <li><?= htmlspecialchars((string) (($theme_pack['background_mode'] ?? 'color') === 'gradient'
                                                        ? l('ai_plan.app_review_editor_background_gradient') . ': ' . (($theme_pack['gradient_start'] ?? '') . ' / ' . ($theme_pack['gradient_end'] ?? ''))
                                                        : l('ai_plan.app_review_editor_background_color') . ': ' . ($theme_pack['background_color'] ?? '')), ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endif ?>
                                                <?php if(!empty($theme_pack['primary_block_background'])): ?>
                                                    <li><?= htmlspecialchars((string) (l('ai_plan.app_review_editor_primary_color') . ': ' . ($theme_pack['primary_block_background'] ?? '')), ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endif ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>

                                    <?php if($has_primary_block_plan): ?>
                                        <div class="ai-plan-review-detail-card">
                                            <h3><?= l('ai_plan.app_review_editor_primary_block') ?></h3>
                                            <p class="mb-2 font-weight-bold"><?= htmlspecialchars((string) (($primary_block_plan['label'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php if(!empty($primary_block_plan['reason'])): ?>
                                                <p class="mb-0"><?= htmlspecialchars((string) ($primary_block_plan['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php endif ?>
                                        </div>
                                    <?php endif ?>

                                    <?php if(!empty($copy_suggestions)): ?>
                                        <div class="ai-plan-review-detail-card full">
                                            <h3><?= l('ai_plan.app_review_editor_copy') ?></h3>
                                            <ul class="ai-plan-review-list mb-0">
                                                <?php foreach($copy_suggestions as $copy_item): ?>
                                                    <li><?= htmlspecialchars((string) (($copy_item['label'] ?? '') ? ($copy_item['label'] . ': ' . ($copy_item['value'] ?? '')) : ($copy_item['value'] ?? '')), ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>

                                    <?php if(!empty($layout_actions)): ?>
                                        <div class="ai-plan-review-detail-card full">
                                            <h3><?= l('ai_plan.app_review_editor_layout') ?></h3>
                                            <ul class="ai-plan-review-list mb-0">
                                                <?php foreach($layout_actions as $layout_item): ?>
                                                    <li><?= htmlspecialchars((string) (($layout_item['label'] ?? '') ? ($layout_item['label'] . ': ' . ($layout_item['why'] ?? '')) : ($layout_item['why'] ?? '')), ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </details>
                    <?php endif ?>

                    <?php if(!empty($evolution_active_cycle)): ?>
                        <details class="ai-plan-review-disclosure" data-accordion-item="app-review-details">
                            <summary><?= l('link.settings.ai_evolution_title') ?></summary>
                            <div class="ai-plan-review-disclosure-body">
                                <div class="ai-plan-review-detail-grid" style="margin-top:0;">
                                    <div class="ai-plan-review-detail-card">
                                        <h3><?= l('link.settings.ai_evolution_theme_status') ?></h3>
                                        <p class="mb-2 font-weight-bold"><?= !empty($evolution_active_cycle['applied']['theme_applied_at']) ? l('link.settings.ai_evolution_status_measured') : l('link.settings.ai_evolution_status_pending') ?></p>
                                        <p class="mb-0 text-muted"><?= !empty($evolution_active_cycle['applied']['theme_applied_at']) ? \Altum\Date::get($evolution_active_cycle['applied']['theme_applied_at'], 2) : l('link.settings.ai_evolution_waiting_apply') ?></p>
                                    </div>
                                    <div class="ai-plan-review-detail-card">
                                        <h3><?= l('link.settings.ai_evolution_layout_status') ?></h3>
                                        <p class="mb-2 font-weight-bold"><?= !empty($evolution_active_cycle['applied']['layout_applied_at']) ? l('link.settings.ai_evolution_status_measured') : (!empty($evolution_active_cycle['applied']['layout_reverted_at']) ? l('link.settings.ai_layout_restore_badge') : l('link.settings.ai_evolution_status_pending')) ?></p>
                                        <p class="mb-0 text-muted">
                                            <?php if(!empty($evolution_active_cycle['applied']['layout_applied_at'])): ?>
                                                <?= \Altum\Date::get($evolution_active_cycle['applied']['layout_applied_at'], 2) ?>
                                            <?php elseif(!empty($evolution_active_cycle['applied']['layout_reverted_at'])): ?>
                                                <?= \Altum\Date::get($evolution_active_cycle['applied']['layout_reverted_at'], 2) ?>
                                            <?php else: ?>
                                                <?= l('link.settings.ai_evolution_waiting_apply') ?>
                                            <?php endif ?>
                                        </p>
                                    </div>
                                    <?php foreach(['evaluation_7d' => l('link.settings.ai_evolution_window_7d'), 'evaluation_30d' => l('link.settings.ai_evolution_window_30d')] as $measurement_key => $measurement_label): ?>
                                        <?php $measurement = is_array($evolution_active_cycle[$measurement_key] ?? null) ? $evolution_active_cycle[$measurement_key] : []; ?>
                                        <div class="ai-plan-review-detail-card">
                                            <h3><?= htmlspecialchars($measurement_label, ENT_QUOTES, 'UTF-8') ?></h3>
                                            <p class="mb-2 font-weight-bold"><?= $render_app_review_evolution_status((string) ($measurement['status'] ?? 'pending')) ?></p>
                                            <p class="mb-2 text-muted">
                                                <?php if(!empty($measurement['measured_at'])): ?>
                                                    <?= \Altum\Date::get($measurement['measured_at'], 2) ?>
                                                <?php elseif(($measurement['status'] ?? 'pending') === 'ready'): ?>
                                                    <?= l('link.settings.ai_evolution_ready_text') ?>
                                                <?php else: ?>
                                                    <?= l('link.settings.ai_evolution_waiting_measurement') ?>
                                                <?php endif ?>
                                            </p>
                                            <p class="mb-0"><?= htmlspecialchars((string) ($measurement['summary'] ?? l('link.settings.ai_evolution_result_same')), ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        </details>
                    <?php endif ?>

                    <?php if(!empty($app_review_block_attribution['top_signal_blocks']) || !empty($app_review_block_attribution['focus_risk_blocks'])): ?>
                        <details class="ai-plan-review-disclosure" data-accordion-item="app-review-details">
                            <summary><?= l('link.settings.ai_block_attribution_title') ?></summary>
                            <div class="ai-plan-review-disclosure-body">
                                <div class="ai-plan-review-detail-grid" style="margin-top:0;">
                                    <?php if(!empty($app_review_block_attribution['top_signal_blocks'])): ?>
                                        <div class="ai-plan-review-detail-card">
                                            <h3><?= l('link.settings.ai_block_attribution_positive') ?></h3>
                                            <ul class="ai-plan-review-list mb-0">
                                                <?php foreach(($app_review_block_attribution['top_signal_blocks'] ?? []) as $signal_block): ?>
                                                    <li><?= htmlspecialchars((string) (((($signal_block['label'] ?? '') ?: ($signal_block['type'] ?? '-')) . ' - ' . sprintf(l('link.settings.ai_block_attribution_signal_value'), nr((int) ($signal_block['signal_score'] ?? 0)), nr((int) ($signal_block['position'] ?? 0))))), ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>

                                    <?php if(!empty($app_review_block_attribution['focus_risk_blocks'])): ?>
                                        <div class="ai-plan-review-detail-card">
                                            <h3><?= l('link.settings.ai_block_attribution_risk') ?></h3>
                                            <ul class="ai-plan-review-list mb-0">
                                                <?php foreach(($app_review_block_attribution['focus_risk_blocks'] ?? []) as $risk_block): ?>
                                                    <li><?= htmlspecialchars((string) (((($risk_block['label'] ?? '') ?: ($risk_block['type'] ?? '-')) . ' - ' . sprintf(l('link.settings.ai_block_attribution_focus_value'), nr((int) ($risk_block['position'] ?? 0)), nr((int) ($risk_block['signal_score'] ?? 0))))), ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </details>
                    <?php endif ?>

                    <?php if(!empty($signal_protection_summary['has_items'])): ?>
                        <details class="ai-plan-review-disclosure" data-accordion-item="app-review-details">
                            <summary><?= l('link.settings.ai_signal_protection_title') ?></summary>
                            <div class="ai-plan-review-disclosure-body">
                                <?php if(!empty($signal_protection_summary['summary'])): ?>
                                    <p class="mb-3"><?= htmlspecialchars((string) $signal_protection_summary['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif ?>

                                <div class="ai-plan-review-detail-grid" style="margin-top:0;">
                                    <?php if(!empty($signal_protection_summary['kept_signal_blocks'])): ?>
                                        <div class="ai-plan-review-detail-card">
                                            <h3><?= l('link.settings.ai_signal_protection_kept') ?></h3>
                                            <ul class="ai-plan-review-list mb-0">
                                                <?php foreach(($signal_protection_summary['kept_signal_blocks'] ?? []) as $signal_item): ?>
                                                    <li><?= htmlspecialchars((string) (((($signal_item['label'] ?? '') ?: '-') . ' - ' . ($signal_item['reason'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>

                                    <?php if(!empty($signal_protection_summary['repositioned_focus_blocks'])): ?>
                                        <div class="ai-plan-review-detail-card">
                                            <h3><?= l('link.settings.ai_signal_protection_moved') ?></h3>
                                            <ul class="ai-plan-review-list mb-0">
                                                <?php foreach(($signal_protection_summary['repositioned_focus_blocks'] ?? []) as $focus_item): ?>
                                                    <li><?= htmlspecialchars((string) (((($focus_item['label'] ?? '') ?: '-') . ' - ' . ($focus_item['reason'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </details>
                    <?php endif ?>

                    <?php if(!empty($review['design_notes']) || !empty($review['keep_doing'])): ?>
                        <details class="ai-plan-review-disclosure" data-accordion-item="app-review-details">
                            <summary><?= l('ai_plan.app_review_design_notes') ?> &amp; <?= l('ai_plan.app_review_keep_doing') ?></summary>
                            <div class="ai-plan-review-disclosure-body">
                                <div class="ai-plan-review-detail-grid" style="margin-top:0;">
                                    <?php if(!empty($review['design_notes'])): ?>
                                        <div class="ai-plan-review-detail-card">
                                            <h3><?= l('ai_plan.app_review_design_notes') ?></h3>
                                            <ul class="ai-plan-review-list mb-0">
                                                <?php foreach(($review['design_notes'] ?? []) as $note): ?>
                                                    <li><?= htmlspecialchars((string) $note, ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>

                                    <?php if(!empty($review['keep_doing'])): ?>
                                        <div class="ai-plan-review-detail-card">
                                            <h3><?= l('ai_plan.app_review_keep_doing') ?></h3>
                                            <ul class="ai-plan-review-list mb-0">
                                                <?php foreach(($review['keep_doing'] ?? []) as $note): ?>
                                                    <li><?= htmlspecialchars((string) $note, ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </details>
                    <?php endif ?>

                    <details class="ai-plan-review-disclosure" data-accordion-item="app-review-details">
                        <summary><?= l('ai_plan.app_review_quality_comparison') ?></summary>
                        <div class="ai-plan-review-disclosure-body">
                            <div class="ai-plan-review-comparison-list">
                                <?php foreach(($quality_payload['comparisons'] ?? []) as $comparison): ?>
                                    <div class="ai-plan-review-comparison-row">
                                        <div>
                                            <div class="font-weight-bold"><?= htmlspecialchars((string) ($comparison['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php $comparison_format = (string) ($comparison['format'] ?? 'number'); ?>
                                            <?php $comparison_target = $comparison_format === 'percent' ? number_format((float) ($comparison['target'] ?? 0), 1) . '%' : nr((int) ($comparison['target'] ?? 0)); ?>
                                            <?php $comparison_current = $comparison_format === 'percent' ? number_format((float) ($comparison['current'] ?? 0), 1) . '%' : nr((int) ($comparison['current'] ?? 0)); ?>
                                            <div class="ai-plan-review-comparison-target"><?= sprintf(l('ai_plan.app_review_quality_target'), $comparison_target) ?></div>
                                        </div>
                                        <div class="font-weight-bold"><?= $comparison_current ?></div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}; ?>
<?php $render_app_review_teaser_card = static function(string $page_url, bool $is_profile_complete, bool $is_accessible, string $locked_reason, ?array $latest_review, array $quality_payload, array $ai_growth_access = [], bool $is_app_review_locked = false, ?string $app_review_next_at = null, bool $compact = false, array $editor_actions = []): string {
    $tier = (string) ($ai_growth_access['tier'] ?? 'none');
    $starter = (array) ($ai_growth_access['starter'] ?? []);
    $starter_app_review_remaining = (int) ($starter['app_review_remaining'] ?? 0);
    $review_cooldown_days = (int) (($ai_growth_access['app_review']['cooldown_days'] ?? 0));
    $growth_signal_30d = (int) ($ai_growth_access['growth_signal_30d'] ?? 0);
    $editor_link_id = max(0, (int) ($editor_actions['link_id'] ?? 0));
    $show_editor_actions = !$compact && $editor_link_id > 0 && (!empty($editor_actions['has_any']) || !empty($latest_review));
    $can_apply_blocks = !empty($editor_actions['can_apply_blocks']);
    $can_apply_colors = !empty($editor_actions['can_apply_colors']);
    $can_restore = !empty($editor_actions['can_restore']);
    $actions_freshness = (array) ($editor_actions['freshness'] ?? []);
    $actions_stale = !empty($actions_freshness['is_stale']);
    $actions_notification_id = 'ai-plan-app-review-actions-notification-' . $editor_link_id . ($compact ? '-compact' : '');

    $summary_text = $latest_review ? (string) ($quality_payload['summary'] ?? '') : '';
    $reason_text = l('ai_plan.app_review_quality_teaser_reason');

    if(!$is_profile_complete) {
        $summary_text = l('ai_plan.app_review_locked_profile');
        $reason_text = 'Prvo dovrši profil kako bi AI znao cilj, publiku i fokus aplikacije.';
    } elseif(!$is_accessible) {
        $summary_text = $locked_reason;
        if($tier === 'pro_start') {
            $summary_text = $starter_app_review_remaining > 0
                ? 'PRO Start uključuje 1 početnu analizu glavne FCC aplikacije.'
                : 'Početna analiza je iskorištena. Nova analiza se ponovno otključava kad skupiš 15+ klikova i prijava u zadnjih 30 dana.';
        }
        $reason_text = $tier === 'none'
            ? 'AI analiza aplikacije dostupna je unutar aktivnog PRO paketa.'
            : 'Klikovi i prijave trenutno su na ' . nr($growth_signal_30d) . '.';
    } elseif($tier === 'pro_start') {
        $summary_text = $latest_review
            ? 'Početna analiza je spremljena. Sljedeća analiza se otključava kad skupiš 15+ klikova i prijava u zadnjih 30 dana.'
            : 'PRO Start uključuje 1 početnu analizu glavne FCC aplikacije kako bi odmah dobio jasan smjer promjena.';
        $reason_text = 'Nakon 15+ klikova i prijava otključavaš redovni ritam analiza i tjednih planova.';
    } elseif(in_array($tier, ['pro_active', 'pro_vip', 'admin'], true)) {
        $cadence_text = in_array($tier, ['pro_active', 'pro_vip'], true) ? 'svakih 7 dana' : 'bez ograničenja u testnom modu';
        $summary_text = $latest_review
            ? ($is_app_review_locked
                ? 'Zadnja analiza je spremljena. Nova analiza aplikacije otključava se ' . $cadence_text . '.'
                : 'Analiza aplikacije je aktivna. Ovdje možeš pregledati zadnje preporuke i odmah pokrenuti novu analizu.')
            : 'Analiza aplikacije je otključana. Pokreni pregled kako bi AI provjerio redoslijed blokova i glavni put prema kontaktu.';
        $reason_text = 'Tvoj AI status sada uključuje novu analizu aplikacije ' . $cadence_text . '.';
    }

    ob_start();
    ?>
    <div class="card ai-plan-card ai-plan-tool-teaser<?= $compact ? ' is-compact' : '' ?>">
        <div class="card-body ai-plan-tool-teaser-body">
            <div class="ai-plan-tool-teaser-copy">
                <span class="ai-plan-tool-teaser-kicker"><?= l('ai_plan.app_review_badge') ?></span>

                <div>
                    <h2><?= l('ai_plan.optional_tool_title') ?></h2>
                    <p class="text-muted mb-0"><?= l('ai_plan.optional_tool_text') ?></p>
                </div>

                <div class="ai-plan-tool-teaser-meta">
                    <?php if($latest_review): ?>
                        <span class="ai-plan-chip active"><?= l('ai_plan.plan_generated_at') ?>: <?= \Altum\Date::get($latest_review['generated_at'], 2) ?></span>
                    <?php else: ?>
                        <span class="ai-plan-chip <?= $is_profile_complete ? 'active' : 'locked' ?>"><?= $is_profile_complete ? l('ai_plan.status_ready') : l('ai_plan.metric_unlock_profile_pending') ?></span>
                    <?php endif ?>
                </div>

                <div class="ai-plan-tool-teaser-summary">
                    <?= htmlspecialchars($summary_text, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <?php if($show_editor_actions): ?>
                    <div class="ai-plan-tool-teaser-actions">
                        <button
                            type="button"
                            class="ai-plan-tool-teaser-action is-primary is-wide js-ai-plan-editor-action"
                            data-request-type="apply_ai_block_bundle"
                            data-link-id="<?= $editor_link_id ?>"
                            data-notification-target="#<?= htmlspecialchars($actions_notification_id, ENT_QUOTES, 'UTF-8') ?>"
                            data-ai-stale="<?= $actions_stale ? '1' : '0' ?>"
                            data-ai-stale-message="<?= htmlspecialchars((string) ($actions_freshness['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            <?= $can_apply_blocks ? null : 'disabled="disabled"' ?>
                        >
                            <i class="fas fa-fw fa-layer-group"></i>
                            <span><?= htmlspecialchars(l('link.settings.ai_block_bundle_apply'), ENT_QUOTES, 'UTF-8') ?></span>
                        </button>

                        <button
                            type="button"
                            class="ai-plan-tool-teaser-action js-ai-plan-editor-action"
                            data-request-type="apply_ai_color_bundle"
                            data-link-id="<?= $editor_link_id ?>"
                            data-notification-target="#<?= htmlspecialchars($actions_notification_id, ENT_QUOTES, 'UTF-8') ?>"
                            data-ai-stale="<?= $actions_stale ? '1' : '0' ?>"
                            data-ai-stale-message="<?= htmlspecialchars((string) ($actions_freshness['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            <?= $can_apply_colors ? null : 'disabled="disabled"' ?>
                        >
                            <i class="fas fa-fw fa-palette"></i>
                            <span><?= htmlspecialchars(l('link.settings.ai_color_bundle_apply'), ENT_QUOTES, 'UTF-8') ?></span>
                        </button>

                        <button
                            type="button"
                            class="ai-plan-tool-teaser-action is-muted js-ai-plan-editor-action"
                            data-request-type="restore_ai_bundle_backup"
                            data-link-id="<?= $editor_link_id ?>"
                            data-notification-target="#<?= htmlspecialchars($actions_notification_id, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $can_restore ? null : 'disabled="disabled"' ?>
                        >
                            <i class="fas fa-fw fa-undo"></i>
                            <span><?= htmlspecialchars(l('link.settings.ai_bundle_restore'), ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                    </div>

                    <?php if($actions_stale): ?>
                        <div class="ai-plan-tool-teaser-notice is-warning" style="margin-top:.85rem;">
                            <?= htmlspecialchars((string) ($actions_freshness['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif ?>

                    <div id="<?= htmlspecialchars($actions_notification_id, ENT_QUOTES, 'UTF-8') ?>" class="ai-plan-tool-teaser-notification"></div>
                <?php endif ?>
            </div>

            <div class="ai-plan-tool-teaser-side">
                <div class="ai-plan-tool-teaser-score">
                    <div class="ai-plan-tool-teaser-score-value"><?= nr((int) ($quality_payload['score'] ?? 0)) ?></div>
                    <div class="ai-plan-tool-teaser-score-copy">
                        <div class="ai-plan-tool-teaser-score-label"><?= l('ai_plan.app_review_quality_title') ?></div>
                        <div class="ai-plan-tool-teaser-score-level"><?= htmlspecialchars((string) ($quality_payload['level_label'] ?? l('ai_plan.app_review_quality_level.foundation')), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>

                <div class="ai-plan-tool-teaser-cta">
                    <?php if($is_accessible): ?>
                        <a href="<?= htmlspecialchars($page_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary"><?= l('ai_plan.cta_go_app_review_direct') ?></a>
                    <?php else: ?>
                        <a href="#" class="btn btn-outline-primary disabled pointer-events-all ai-plan-disabled-link" data-tooltip title="<?= htmlspecialchars($locked_reason, ENT_QUOTES, 'UTF-8') ?>" onclick="event.preventDefault();"><?= l('ai_plan.cta_go_app_review_direct') ?></a>
                    <?php endif ?>
                    <div class="ai-plan-tool-teaser-reason"><?= htmlspecialchars($reason_text, ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <ul class="ai-plan-tool-teaser-points">
                    <li><?= $latest_review ? l('ai_plan.app_review_priority_actions') : l('ai_plan.app_review_footer') ?></li>
                    <li><?= l('ai_plan.app_review_design_notes') ?></li>
                    <li><?= l('ai_plan.app_review_block_order') ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}; ?>
<?php $render_weekly_cycle_panel = static function(array $steps, string $next_value): string {
    ob_start();
    ?>
    <div class="ai-plan-cycle-panel mb-3">
        <div class="ai-plan-cycle-head">
            <div>
                <div class="ai-plan-cycle-title"><?= l('ai_plan.weekly_cycle_title') ?></div>
                <p class="ai-plan-cycle-text"><?= l('ai_plan.weekly_cycle_text') ?></p>
            </div>
            <div class="ai-plan-cycle-next">
                <div class="ai-plan-cycle-next-label"><?= l('ai_plan.weekly_cycle_next_label') ?></div>
                <div class="ai-plan-cycle-next-value"><?= htmlspecialchars($next_value, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
        <div class="ai-plan-cycle-grid">
            <?php foreach($steps as $index => $step): ?>
                <div class="ai-plan-cycle-card <?= htmlspecialchars((string) ($step['status'] ?? 'locked'), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="ai-plan-cycle-step"><?= ($index + 1) ?>. <?= l('ai_plan.step_status_' . (($step['status'] ?? 'locked'))) ?></div>
                    <div class="ai-plan-cycle-card-title"><?= htmlspecialchars((string) ($step['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="ai-plan-cycle-card-text"><?= htmlspecialchars((string) ($step['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}; ?>
<?php $render_weekly_refresh_panel = static function(string $profile_url): string {
    ob_start();
    ?>
    <div class="ai-plan-cycle-panel mb-3">
        <div class="ai-plan-cycle-head">
            <div>
                <div class="ai-plan-cycle-title">Prije novog plana po želji osvježi smjer rada</div>
                <p class="ai-plan-cycle-text">Prethodni ciklus je zatvoren. Ako u novom planu želiš drugi fokus, ponudu, ritam ili publiku, prvo ažuriraj Profil i smjer rada pa zatim ispuni novi tjedni unos.</p>
            </div>
            <div class="ai-plan-cycle-next">
                <div class="ai-plan-cycle-next-label">Opcionalno prije plana</div>
                <div class="ai-plan-cycle-next-value">Profil i smjer rada</div>
            </div>
        </div>
        <div class="ai-plan-cycle-grid" style="grid-template-columns:minmax(0, 1fr);">
            <a href="<?= htmlspecialchars($profile_url, ENT_QUOTES, 'UTF-8') ?>" class="ai-plan-cycle-card current" style="text-decoration:none;color:inherit;">
                <div class="ai-plan-cycle-step">Opcionalno prije novog plana</div>
                <div class="ai-plan-cycle-card-title">Profil i smjer rada</div>
                <div class="ai-plan-cycle-card-text">Otvori profil ako želiš promijeniti cilj, publiku, fokus ili način rada prije nego AI složi sljedeći plan za 7 dana.</div>
            </a>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}; ?>
<?php $render_weekly_plan_result = static function(?array $plan, ?array $outcome, array $feedback_payload, bool $show_feedback): string {
    if(empty($plan)) {
        return '';
    }

    ob_start();
    ?>
    <div class="card ai-plan-card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;">
            <div>
                <h2 class="h5 mb-1"><?= l('ai_plan.plan_title') ?></h2>
                <p class="text-muted mb-0"><?= l('ai_plan.plan_text') ?></p>
            </div>
            <?php if(!empty($plan['generated_at'])): ?>
                <div class="small text-muted"><?= l('ai_plan.plan_generated_at') ?>: <?= \Altum\Date::get($plan['generated_at'], 2) ?></div>
            <?php endif ?>
        </div>

        <?php if($show_feedback && !empty($feedback_payload['has_feedback'])): ?>
            <div class="ai-plan-outcome-box mb-3">
                <div class="h6 mb-1"><?= l('ai_plan.feedback_loop_title') ?></div>
                <div class="text-muted small mb-3"><?= l('ai_plan.feedback_loop_text') ?></div>
                <?php if(!empty($feedback_payload['previous_focus'])): ?>
                    <div class="small text-muted mb-1"><?= l('ai_plan.feedback_loop_previous_focus') ?></div>
                    <div class="mb-3 font-weight-bold"><?= htmlspecialchars($feedback_payload['previous_focus'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif ?>
                <div class="small text-muted mb-1"><?= l('ai_plan.feedback_loop_completion') ?></div>
                <div class="mb-3"><?= htmlspecialchars($feedback_payload['completion_level'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted mb-1"><?= l('ai_plan.feedback_loop_best_response') ?></div>
                <div class="mb-3"><?= htmlspecialchars($feedback_payload['best_response'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted mb-1"><?= l('ai_plan.feedback_loop_blocker') ?></div>
                <div class="mb-3"><?= htmlspecialchars($feedback_payload['main_blocker_now'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted mb-1"><?= l('ai_plan.feedback_loop_adjustment') ?></div>
                <div class="mb-3"><?= htmlspecialchars($feedback_payload['next_adjustment'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <?php if(!empty($feedback_payload['palette_feedback'])): ?>
                    <div class="small text-muted mb-1"><?= l('ai_plan.outcome_palette_feedback') ?></div>
                    <div class="mb-2"><?= htmlspecialchars($feedback_payload['palette_feedback'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if(!empty($feedback_payload['palette_feedback_note'])): ?>
                        <div class="small text-muted mb-1"><?= l('ai_plan.outcome_palette_feedback_note') ?></div>
                        <div class="mb-0"><?= htmlspecialchars($feedback_payload['palette_feedback_note'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif ?>
                <?php endif ?>
            </div>
        <?php endif ?>

        <div class="ai-plan-plan-intro">
            <?php if(!empty($plan['coach_intro'])): ?>
                <div class="ai-plan-plan-panel">
                    <div class="ai-plan-plan-label"><?= l('ai_plan.plan_coach_intro') ?></div>
                    <div class="ai-plan-plan-copy"><?= htmlspecialchars($plan['coach_intro'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif ?>

            <div class="ai-plan-plan-panel primary">
                <div class="ai-plan-plan-label"><?= l('ai_plan.plan_focus') ?></div>
                <div class="ai-plan-plan-headline">
                    <strong><?= htmlspecialchars($plan['headline'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                    <div class="mb-2"><?= htmlspecialchars($plan['summary'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <div><?= htmlspecialchars($plan['focus'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php if(!empty($plan['priority_channels'])): ?>
                    <div class="d-flex flex-wrap mt-3" style="gap:.65rem;">
                        <?php foreach(($plan['priority_channels'] ?? []) as $priority_channel): ?>
                            <span class="ai-plan-pill"><?= htmlspecialchars($priority_channel, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>

            <?php if(!empty($plan['brutal_truth'])): ?>
                <div class="ai-plan-plan-panel truth">
                    <div class="ai-plan-plan-label"><?= l('ai_plan.plan_brutal_truth') ?></div>
                    <div class="ai-plan-plan-copy" style="font-weight:800; color:#f8fafc;"><?= htmlspecialchars($plan['brutal_truth'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif ?>

            <?php if(!empty($plan['power_move'])): ?>
                <div class="ai-plan-plan-panel move">
                    <div class="ai-plan-plan-label"><?= l('ai_plan.plan_power_move') ?></div>
                    <div class="ai-plan-plan-copy emphasis"><?= htmlspecialchars($plan['power_move'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif ?>

            <?php if(!empty($plan['why_this_week'])): ?>
                <div class="ai-plan-plan-panel">
                    <div class="ai-plan-plan-label"><?= l('ai_plan.plan_why_this_week') ?></div>
                    <div class="ai-plan-plan-copy"><?= htmlspecialchars($plan['why_this_week'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif ?>
        </div>

        <div class="ai-plan-plan-block">
            <div class="ai-plan-day-stream">
                <?php foreach(($plan['daily_plan'] ?? []) as $day_plan): ?>
                    <div class="ai-plan-day-row">
                        <div class="ai-plan-day-side">
                            <div class="ai-plan-day-badge"><?= htmlspecialchars($day_plan['day'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            <h4 class="ai-plan-day-title"><?= htmlspecialchars($day_plan['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h4>
                        </div>
                        <div class="ai-plan-day-marker"><span class="ai-plan-day-node"></span></div>
                        <div class="ai-plan-day-content ai-plan-day-card">
                            <ul class="ai-plan-task-list"><?php foreach(($day_plan['tasks'] ?? []) as $task): ?><li class="ai-plan-task-item"><?= htmlspecialchars($task, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach ?></ul>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="ai-plan-advice-stack">
                <div class="ai-plan-advice-row is-content">
                    <div class="ai-plan-advice-side">
                        <div>
                            <div class="ai-plan-advice-kicker"><?= l('ai_plan.plan_content_kicker') ?></div>
                            <div class="ai-plan-advice-title"><?= l('ai_plan.plan_content_ideas') ?></div>
                        </div>
                        <div class="ai-plan-advice-note"><?= l('ai_plan.plan_content_note') ?></div>
                    </div>
                    <div class="ai-plan-advice-copy"><?php if(!empty($plan['content_ideas'])): ?><ul class="ai-plan-task-list"><?php foreach($plan['content_ideas'] as $idea): ?><li class="ai-plan-task-item"><?= htmlspecialchars($idea, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach ?></ul><?php else: ?><div class="text-muted small mb-0"><?= l('ai_plan.plan_content_ideas_empty') ?></div><?php endif ?></div>
                </div>

                <div class="ai-plan-advice-row is-coach">
                    <div class="ai-plan-advice-side">
                        <div>
                            <div class="ai-plan-advice-kicker"><?= l('ai_plan.plan_coach_kicker') ?></div>
                            <div class="ai-plan-advice-title"><?= l('ai_plan.plan_coach_ideas') ?></div>
                        </div>
                        <div class="ai-plan-advice-note"><?php if(!empty($plan['encouragement'])): ?><?= htmlspecialchars($plan['encouragement'], ENT_QUOTES, 'UTF-8') ?><?php else: ?><?= l('ai_plan.plan_coach_note_fallback') ?><?php endif ?></div>
                    </div>
                    <div class="ai-plan-advice-copy"><?php if(!empty($plan['coach_ideas'])): ?><ul class="ai-plan-task-list"><?php foreach($plan['coach_ideas'] as $item): ?><li class="ai-plan-task-item"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach ?></ul><?php else: ?><div class="text-muted small mb-0"><?= l('ai_plan.plan_content_ideas_empty') ?></div><?php endif ?></div>
                </div>

                <div class="ai-plan-advice-row is-boundary">
                    <div class="ai-plan-advice-side">
                        <div>
                            <div class="ai-plan-advice-kicker"><?= l('ai_plan.plan_boundary_kicker') ?></div>
                            <div class="ai-plan-advice-title"><?= l('ai_plan.plan_do_not_do') ?></div>
                        </div>
                        <div class="ai-plan-advice-note"><?= l('ai_plan.plan_boundary_note') ?></div>
                    </div>
                    <div class="ai-plan-advice-copy"><?php if(!empty($plan['do_not_do'])): ?><ul class="ai-plan-task-list"><?php foreach($plan['do_not_do'] as $item): ?><li class="ai-plan-task-item"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach ?></ul><?php else: ?><div class="text-muted small mb-0"><?= l('ai_plan.plan_do_not_do_empty') ?></div><?php endif ?></div>
                </div>
            </div>
        </div>
    </div></div>

    <div class="card ai-plan-card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;">
            <div>
                <h2 class="h5 mb-1"><?= l('ai_plan.outcome_title') ?></h2>
                <p class="text-muted mb-0"><?= l('ai_plan.outcome_text') ?></p>
            </div>
            <?php if($outcome): ?>
                <div class="small text-muted"><?= l('ai_plan.last_updated') ?>: <?= \Altum\Date::get($outcome['submitted_at'], 2) ?></div>
            <?php endif ?>
        </div>

        <?php if($outcome): ?>
            <div class="ai-plan-outcome-box mb-3">
                <div class="small text-muted mb-1"><?= l('ai_plan.outcome_completion_level') ?></div>
                <div class="font-weight-bold mb-3"><?= l('ai_plan.option.completion_level.' . $outcome['completion_level']) ?></div>
                <div class="small text-muted mb-1"><?= l('ai_plan.outcome_best_response') ?></div>
                <div class="mb-3"><?= htmlspecialchars($outcome['best_response'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted mb-1"><?= l('ai_plan.outcome_main_blocker_now') ?></div>
                <div class="mb-3"><?= htmlspecialchars($outcome['main_blocker_now'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted mb-1"><?= l('ai_plan.outcome_biggest_lesson') ?></div>
                <div class="mb-3"><?= htmlspecialchars($outcome['biggest_lesson'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted mb-1"><?= l('ai_plan.outcome_next_adjustment') ?></div>
                <div class="mb-3"><?= htmlspecialchars($outcome['next_adjustment'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php if(!empty($outcome['palette_feedback'])): ?>
                    <div class="small text-muted mb-1"><?= l('ai_plan.outcome_palette_feedback') ?></div>
                    <div class="mb-2"><?= l('ai_plan.option.palette_feedback.' . $outcome['palette_feedback']) ?></div>
                    <?php if(!empty($outcome['palette_feedback_note'])): ?>
                        <div class="small text-muted mb-1"><?= l('ai_plan.outcome_palette_feedback_note') ?></div>
                        <div class="mb-0"><?= htmlspecialchars($outcome['palette_feedback_note'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif ?>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div></div>
<?php
    return (string) ob_get_clean();
}; ?>
<?php $render_weekly_plan_history_strip = static function(array $weekly_plans, ?array $latest_weekly_plan, ?array $active_weekly_plan, ?array $active_weekly_outcome, string $active_generated_at, array $feedback_payload, callable $render_weekly_plan_result, string $section = 'plan'): string {
    ob_start();
    ?>
    <?php if(!empty($weekly_plans)): ?>
        <div class="ai-plan-history-strip mb-3">
            <div class="ai-plan-history-strip-head">
                <div>
                    <div class="font-weight-bold mb-1"><?= l('ai_plan.plan_history_title') ?></div>
                    <div class="small text-muted"><?= l('ai_plan.plan_history_text') ?></div>
                </div>
            </div>
            <div class="ai-plan-history-list">
                <?php foreach(array_slice($weekly_plans, 0, 3) as $history_plan): ?>
                    <?php
                    $history_plan_generated_at = (string) ($history_plan['generated_at'] ?? '');
                    $history_plan_active = $history_plan_generated_at !== '' && $history_plan_generated_at === $active_generated_at;
                    $history_plan_link = $history_plan_active
                        ? url('ai-plan?section=' . urlencode($section) . '&plan_history=1')
                        : url('ai-plan?section=' . urlencode($section) . '&plan_generated_at=' . urlencode($history_plan_generated_at));
                    ?>
                    <div class="ai-plan-history-entry <?= $history_plan_active ? 'active' : '' ?>">
                        <div class="ai-plan-history-card <?= $history_plan_active ? 'active' : '' ?>">
                            <div class="ai-plan-history-copy">
                                <div class="ai-plan-history-title"><?= htmlspecialchars((string) (($history_plan['headline'] ?? '') ?: ($history_plan['focus'] ?? l('ai_plan.plan_title'))), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="ai-plan-history-meta"><?= l('ai_plan.plan_generated_at') ?>: <?= !empty($history_plan['generated_at']) ? \Altum\Date::get($history_plan['generated_at'], 2) : '-' ?></div>
                                <div class="ai-plan-history-note"><?= htmlspecialchars((string) (($history_plan['summary'] ?? '') ?: ($history_plan['focus'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="ai-plan-history-side">
                                <a href="<?= htmlspecialchars($history_plan_link, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary ai-plan-history-action"><?= $history_plan_active ? l('ai_plan.plan_history_active') : l('ai_plan.plan_history_open') ?></a>
                            </div>
                        </div>
                        <?php if($history_plan_active && $active_weekly_plan): ?>
                            <div class="ai-plan-history-expanded ai-plan-plan-expanded">
                                <?= $render_weekly_plan_result(
                                    $active_weekly_plan,
                                    $active_weekly_outcome,
                                    $feedback_payload,
                                    !empty($feedback_payload['has_feedback']) && (($active_weekly_plan['generated_at'] ?? '') === ($latest_weekly_plan['generated_at'] ?? ''))
                                ) ?>
                            </div>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    <?php else: ?>
        <div class="ai-plan-history-empty mb-3"><?= l('ai_plan.plan_history_empty') ?></div>
    <?php endif ?>
    <?php
    return (string) ob_get_clean();
}; ?>
<?php $render_weekly_outcome_form = static function(string $action_url, ?array $outcome = null, string $footer_text = '', ?array $plan = null, array $palette_context = []): string {
    ob_start();
    $selected_link_id = max(0, (int) ($outcome['selected_link_id'] ?? ($palette_context['selected_link_id'] ?? 0)));
    $app_review_generated_at = (string) ($outcome['app_review_generated_at'] ?? ($palette_context['app_review_generated_at'] ?? ''));
    $app_review_review_key = (string) ($outcome['app_review_review_key'] ?? ($palette_context['app_review_review_key'] ?? $app_review_generated_at));
    $outcome_is_ready = !empty($outcome['completion_level'])
        && !empty(trim((string) ($outcome['best_response'] ?? '')))
        && !empty(trim((string) ($outcome['main_blocker_now'] ?? '')))
        && !empty(trim((string) ($outcome['biggest_lesson'] ?? '')))
        && !empty(trim((string) ($outcome['next_adjustment'] ?? '')))
        && !empty($outcome['palette_feedback']);
    ?>
    <div class="card ai-plan-card mb-3" id="ai-plan-weekly-outcome-start"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;">
            <div>
                <h2 class="h5 mb-1"><?= l('ai_plan.outcome_title') ?></h2>
                <p class="text-muted mb-0"><?= l('ai_plan.outcome_text') ?></p>
            </div>
            <?php if($outcome): ?>
                <div class="small text-muted"><?= l('ai_plan.last_updated') ?>: <?= \Altum\Date::get($outcome['submitted_at'], 2) ?></div>
            <?php endif ?>
        </div>

        <form action="<?= htmlspecialchars($action_url, ENT_QUOTES, 'UTF-8') ?>" method="post" role="form" class="js-ai-plan-weekly-outcome-form">
            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
            <?php if($plan): ?>
                <input type="hidden" name="outcome_plan_generated_at" value="<?= htmlspecialchars((string) ($plan['generated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="outcome_checkin_submitted_at" value="<?= htmlspecialchars((string) ($plan['checkin_submitted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            <?php endif ?>
            <input type="hidden" name="outcome_selected_link_id" value="<?= $selected_link_id ?>" />
            <input type="hidden" name="outcome_app_review_generated_at" value="<?= htmlspecialchars($app_review_generated_at, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="outcome_app_review_review_key" value="<?= htmlspecialchars($app_review_review_key, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="form-group">
                <label for="completion_level" class="font-weight-bold"><?= l('ai_plan.outcome_completion_level') ?></label>
                <select id="completion_level" name="completion_level" class="custom-select <?= \Altum\Alerts::has_field_errors('completion_level') ? 'is-invalid' : null ?>" data-ai-required="1" required>
                    <option value=""><?= l('global.choose') ?></option>
                    <?php foreach(['strong_progress', 'partial_progress', 'low_execution', 'not_started'] as $option): ?>
                        <option value="<?= $option ?>" <?= ((($outcome['completion_level'] ?? '') === $option) ? 'selected="selected"' : null) ?>><?= l('ai_plan.option.completion_level.' . $option) ?></option>
                    <?php endforeach ?>
                </select>
                <?= \Altum\Alerts::output_field_error('completion_level') ?>
            </div>

            <div class="form-group">
                <label for="best_response" class="font-weight-bold"><?= l('ai_plan.outcome_best_response') ?></label>
                <textarea id="best_response" name="best_response" rows="3" maxlength="800" class="form-control <?= \Altum\Alerts::has_field_errors('best_response') ? 'is-invalid' : null ?>" placeholder="<?= l('ai_plan.outcome_best_response_placeholder') ?>" data-ai-required="1" required><?= htmlspecialchars((string) ($outcome['best_response'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                <?= \Altum\Alerts::output_field_error('best_response') ?>
            </div>

            <div class="form-group">
                <label for="main_blocker_now" class="font-weight-bold"><?= l('ai_plan.outcome_main_blocker_now') ?></label>
                <textarea id="main_blocker_now" name="main_blocker_now" rows="3" maxlength="800" class="form-control <?= \Altum\Alerts::has_field_errors('main_blocker_now') ? 'is-invalid' : null ?>" placeholder="<?= l('ai_plan.outcome_main_blocker_now_placeholder') ?>" data-ai-required="1" required><?= htmlspecialchars((string) ($outcome['main_blocker_now'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                <?= \Altum\Alerts::output_field_error('main_blocker_now') ?>
            </div>

            <div class="form-group">
                <label for="biggest_lesson" class="font-weight-bold"><?= l('ai_plan.outcome_biggest_lesson') ?></label>
                <textarea id="biggest_lesson" name="biggest_lesson" rows="3" maxlength="800" class="form-control <?= \Altum\Alerts::has_field_errors('biggest_lesson') ? 'is-invalid' : null ?>" placeholder="<?= l('ai_plan.outcome_biggest_lesson_placeholder') ?>" data-ai-required="1" required><?= htmlspecialchars((string) ($outcome['biggest_lesson'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                <?= \Altum\Alerts::output_field_error('biggest_lesson') ?>
            </div>

            <div class="form-group mb-0">
                <label for="next_adjustment" class="font-weight-bold"><?= l('ai_plan.outcome_next_adjustment') ?></label>
                <textarea id="next_adjustment" name="next_adjustment" rows="3" maxlength="800" class="form-control <?= \Altum\Alerts::has_field_errors('next_adjustment') ? 'is-invalid' : null ?>" placeholder="<?= l('ai_plan.outcome_next_adjustment_placeholder') ?>" data-ai-required="1" required><?= htmlspecialchars((string) ($outcome['next_adjustment'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                <?= \Altum\Alerts::output_field_error('next_adjustment') ?>
            </div>

            <div class="form-group mt-4">
                <label for="palette_feedback" class="font-weight-bold"><?= l('ai_plan.outcome_palette_feedback') ?></label>
                <select id="palette_feedback" name="palette_feedback" class="custom-select <?= \Altum\Alerts::has_field_errors('palette_feedback') ? 'is-invalid' : null ?>" data-ai-required="1" required>
                    <option value=""><?= l('global.choose') ?></option>
                    <?php foreach(['love_keep', 'good_refine', 'new_direction', 'not_applied'] as $option): ?>
                        <option value="<?= $option ?>" <?= ((($outcome['palette_feedback'] ?? '') === $option) ? 'selected="selected"' : null) ?>><?= l('ai_plan.option.palette_feedback.' . $option) ?></option>
                    <?php endforeach ?>
                </select>
                <small class="form-text text-muted"><?= l('ai_plan.outcome_palette_feedback_help') ?></small>
                <?= \Altum\Alerts::output_field_error('palette_feedback') ?>
            </div>

            <div class="form-group mb-0">
                <label for="palette_feedback_note" class="font-weight-bold"><?= l('ai_plan.outcome_palette_feedback_note') ?></label>
                <textarea id="palette_feedback_note" name="palette_feedback_note" rows="2" maxlength="500" class="form-control" placeholder="<?= l('ai_plan.outcome_palette_feedback_note_placeholder') ?>"><?= htmlspecialchars((string) ($outcome['palette_feedback_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="mt-4 d-flex flex-wrap align-items-center" style="gap:.75rem;">
                <button type="submit" name="save_weekly_outcome" value="1" class="btn btn-primary js-ai-plan-weekly-outcome-submit" <?= $outcome_is_ready ? null : 'disabled="disabled"' ?>><i class="fas fa-check-circle fa-sm mr-1"></i> <?= l('ai_plan.outcome_save') ?></button>
                <span class="text-muted small"><?= htmlspecialchars($footer_text !== '' ? $footer_text : l('ai_plan.outcome_footer'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </form>
    </div></div>
    <?php
    return (string) ob_get_clean();
}; ?>
<?php $render_weekly_outcome_summary = static function(?array $outcome = null, string $title = 'Sažetak prošlog tjedna', string $text = 'Ovo je spremljeni izvještaj prošlog plana koji AI koristi kao kontekst za sljedeći tjedni unos.'): string {
    if(!$outcome) {
        return '';
    }

    $completion_level = (string) ($outcome['completion_level'] ?? '');
    $completion_label = $completion_level !== '' ? l('ai_plan.option.completion_level.' . $completion_level) : '-';

    ob_start();
    ?>
    <div class="card ai-plan-card mb-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;">
            <div>
                <h2 class="h5 mb-1"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-muted mb-0"><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="small text-muted"><?= l('ai_plan.last_updated') ?>: <?= !empty($outcome['submitted_at']) ? \Altum\Date::get($outcome['submitted_at'], 2) : '-' ?></div>
        </div>

        <div class="ai-plan-outcome-box">
            <div class="small text-muted mb-1"><?= l('ai_plan.outcome_completion_level') ?></div>
            <div class="font-weight-bold mb-3"><?= htmlspecialchars((string) $completion_label, ENT_QUOTES, 'UTF-8') ?></div>

            <div class="small text-muted mb-1"><?= l('ai_plan.outcome_best_response') ?></div>
            <div class="mb-3"><?= nl2br(htmlspecialchars((string) ($outcome['best_response'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></div>

            <div class="small text-muted mb-1"><?= l('ai_plan.outcome_main_blocker_now') ?></div>
            <div class="mb-3"><?= nl2br(htmlspecialchars((string) ($outcome['main_blocker_now'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></div>

            <div class="small text-muted mb-1"><?= l('ai_plan.outcome_biggest_lesson') ?></div>
            <div class="mb-3"><?= nl2br(htmlspecialchars((string) ($outcome['biggest_lesson'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></div>

            <div class="small text-muted mb-1"><?= l('ai_plan.outcome_next_adjustment') ?></div>
            <div class="mb-0"><?= nl2br(htmlspecialchars((string) ($outcome['next_adjustment'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></div>
        </div>
    </div></div>
    <?php
    return (string) ob_get_clean();
}; ?>
<?php $ai_plan_sections = ['profile', 'app_review', 'weekly', 'plan']; ?>
<?php $ai_plan_new_weekly_cycle_ready = empty($data->latest_pending_outcome_plan) && !empty($data->latest_weekly_outcome) && $data->is_weekly_plan_eligible && !$data->weekly_is_locked; ?>
<?php $ai_plan_current_step = 'profile'; ?>
<?php if($data->is_profile_complete && empty($data->latest_app_review)) $ai_plan_current_step = 'app_review'; ?>
<?php if(!empty($data->latest_app_review) && !$data->latest_weekly_checkin) $ai_plan_current_step = 'weekly'; ?>
<?php if(!empty($data->latest_weekly_checkin)) $ai_plan_current_step = 'plan'; ?>
<?php if($ai_plan_new_weekly_cycle_ready) $ai_plan_current_step = 'weekly'; ?>
<?php $ai_plan_recommended_section = $ai_plan_current_step === 'plan' ? 'plan' : ($ai_plan_current_step === 'weekly' ? 'weekly' : ($ai_plan_current_step === 'app_review' ? 'app_review' : 'profile')); ?>
<?php $ai_plan_active_section = (string) ($_GET['section'] ?? ''); ?>
<?php $ai_plan_posted_app_review_section = (string) ($_POST['app_review_return_section'] ?? ''); ?>
<?php if(!in_array($ai_plan_posted_app_review_section, $ai_plan_sections, true)) $ai_plan_posted_app_review_section = 'app_review'; ?>
<?php if(!in_array($ai_plan_active_section, $ai_plan_sections, true)) $ai_plan_active_section = isset($_POST['save_profile']) ? 'profile' : (isset($_POST['generate_app_review']) ? $ai_plan_posted_app_review_section : (isset($_POST['save_weekly_checkin']) ? 'weekly' : (isset($_POST['save_weekly_outcome']) ? 'plan' : $ai_plan_recommended_section))); ?>
<?php if($ai_plan_active_section === 'app_review' && !$data->is_profile_complete) $ai_plan_active_section = 'profile'; ?>
<?php if($ai_plan_active_section === 'weekly' && !$data->is_profile_complete) $ai_plan_active_section = 'profile'; ?>
<?php if($ai_plan_active_section === 'weekly' && empty($data->latest_app_review)) $ai_plan_active_section = 'app_review'; ?>
<?php if($ai_plan_active_section === 'plan' && !$data->is_profile_complete) $ai_plan_active_section = 'profile'; ?>
<?php if($ai_plan_active_section === 'plan' && empty($data->latest_app_review)) $ai_plan_active_section = 'app_review'; ?>
<?php if($ai_plan_active_section === 'plan' && !$data->latest_weekly_checkin) $ai_plan_active_section = 'weekly'; ?>
<?php if($ai_plan_new_weekly_cycle_ready && !isset($_GET['section']) && !isset($_POST['save_weekly_checkin']) && !isset($_POST['save_weekly_outcome'])) $ai_plan_active_section = 'weekly'; ?>
<?php if($ai_plan_new_weekly_cycle_ready && $ai_plan_active_section === 'plan' && !isset($_GET['plan_generated_at']) && !isset($_GET['plan_history'])) $ai_plan_active_section = 'weekly'; ?>
<?php $ai_plan_section_urls = [
    'profile' => url('ai-plan?section=profile'),
    'app_review' => url('ai-plan?section=app_review'),
    'weekly' => url('ai-plan?section=weekly'),
    'plan' => url('ai-plan?section=plan'),
]; ?>
<?php $ai_plan_profile_anchor_url = $ai_plan_section_urls['profile'] . '#ai-plan-profile-start'; ?>
<?php $ai_plan_app_review_anchor_url = $ai_plan_section_urls['app_review'] . '#ai-plan-app-review'; ?>
<?php $ai_growth_access = (array) ($data->ai_growth_access_payload ?? []); ?>
<?php $ai_growth_tier = (string) ($ai_growth_access['tier'] ?? 'none'); ?>
<?php $ai_growth_is_pro = (bool) ($ai_growth_access['is_pro'] ?? false); ?>
<?php $ai_growth_signal = (int) ($data->growth_signal_30d ?? ($ai_growth_access['growth_signal_30d'] ?? 0)); ?>
<?php $ai_growth_starter = (array) ($ai_growth_access['starter'] ?? []); ?>
<?php $ai_growth_weekly = (array) ($ai_growth_access['weekly'] ?? []); ?>
<?php $ai_growth_app_review = (array) ($ai_growth_access['app_review'] ?? []); ?>
<?php $ai_plan_unlock_target = 15; ?>
<?php $ai_plan_vip_target = 50; ?>
<?php $ai_plan_signal_missing = max(0, $ai_plan_unlock_target - $ai_growth_signal); ?>
<?php $ai_plan_tier_label = 'Bez AI pristupa'; ?>
<?php if($ai_growth_tier === 'pro_start') $ai_plan_tier_label = 'PRO Start'; ?>
<?php if($ai_growth_tier === 'pro_active') $ai_plan_tier_label = 'PRO Active'; ?>
<?php if($ai_growth_tier === 'pro_vip') $ai_plan_tier_label = 'PRO VIP'; ?>
<?php if($ai_growth_tier === 'admin') $ai_plan_tier_label = 'Admin test'; ?>
<?php $ai_plan_access_summary = 'Za AI analizu aplikacije i tjedni plan potreban je aktivan PRO paket.'; ?>
<?php if($ai_growth_tier === 'pro_start') $ai_plan_access_summary = 'PRO Start uključuje 1 početnu analizu FCC aplikacije i 1 prvi tjedni plan.'; ?>
<?php if($ai_growth_tier === 'pro_active') $ai_plan_access_summary = 'PRO Active je otključan. Tjedni plan je aktivan, a nova analiza aplikacije dostupna je svakih 7 dana.'; ?>
<?php if($ai_growth_tier === 'pro_vip') $ai_plan_access_summary = 'PRO VIP je otključan. Tjedni plan je aktivan, a nova analiza aplikacije dostupna je svakih 7 dana.'; ?>
<?php if($ai_growth_tier === 'admin') $ai_plan_access_summary = 'Administratorski testni način ima puni pristup svim AI koracima.'; ?>
<?php $ai_plan_guide_text = l('ai_plan.guide_profile_text'); ?>
<?php if($ai_plan_recommended_section === 'weekly') $ai_plan_guide_text = $data->is_weekly_plan_eligible ? l('ai_plan.guide_weekly_text') : l('ai_plan.guide_weekly_locked_text'); ?>
<?php if($ai_plan_recommended_section === 'plan') $ai_plan_guide_text = $data->latest_weekly_plan ? l('ai_plan.guide_outcome_text') : l('ai_plan.guide_plan_text'); ?>
<?php $ai_plan_profile_status = !$data->is_profile_complete ? 'current' : 'done'; ?>
<?php $ai_plan_app_review_status = !$data->is_profile_complete ? 'locked' : (!empty($data->latest_app_review) ? 'done' : 'current'); ?>
<?php $ai_plan_weekly_status = !$data->is_profile_complete ? 'locked' : (empty($data->latest_app_review) ? 'locked' : (!$data->latest_weekly_checkin ? 'current' : 'done')); ?>
<?php $ai_plan_plan_status = !$data->latest_weekly_checkin ? 'locked' : 'current'; ?>
<?php if($data->latest_weekly_plan && $data->latest_weekly_outcome) $ai_plan_plan_status = 'done'; ?>
<?php if($data->latest_weekly_checkin && !$data->latest_weekly_plan) $ai_plan_plan_status = 'current'; ?>
<?php if($data->is_profile_complete && !$data->latest_weekly_checkin) $ai_plan_plan_status = 'locked'; ?>
<?php if(empty($data->latest_app_review)) $ai_plan_plan_status = 'locked'; ?>
<?php $ai_plan_primary_cta_label = l('ai_plan.cta_go_profile'); ?>
<?php $ai_plan_primary_cta_url = $ai_plan_profile_anchor_url; ?>
<?php if($ai_plan_current_step === 'app_review') { $ai_plan_primary_cta_label = l('ai_plan.cta_go_app_review_direct'); $ai_plan_primary_cta_url = $ai_plan_app_review_anchor_url; } ?>
<?php if($ai_plan_current_step === 'weekly') { $ai_plan_primary_cta_label = $data->is_weekly_plan_eligible ? l('ai_plan.cta_go_weekly') : l('ai_plan.cta_go_app_review_direct'); $ai_plan_primary_cta_url = $data->is_weekly_plan_eligible ? $ai_plan_section_urls['weekly'] : $ai_plan_app_review_anchor_url; } ?>
<?php if($ai_plan_current_step === 'plan') { $ai_plan_primary_cta_label = l('ai_plan.cta_go_plan'); $ai_plan_primary_cta_url = $ai_plan_section_urls['plan']; } ?>
<?php $ai_plan_metric_next_label = l('ai_plan.sidebar_next_step_label'); ?>
<?php
    $ai_plan_step_profile_url = $ai_plan_section_urls['profile'];
    $ai_plan_step_app_review_url = $app_review_is_accessible ? $ai_plan_app_review_anchor_url : '#';
    $ai_plan_step_weekly_url = $ai_plan_weekly_status === 'locked' ? ($ai_plan_app_review_status !== 'done' ? $ai_plan_app_review_anchor_url : $ai_plan_section_urls['profile']) : $ai_plan_section_urls['weekly'];
    $ai_plan_step_plan_url = $ai_plan_plan_status === 'locked' ? ($ai_plan_weekly_status !== 'done' ? $ai_plan_section_urls['weekly'] : '#') : $ai_plan_section_urls['plan'];
?>
<?php $ai_plan_status_title = l('ai_plan.status_card_title'); ?>
<?php $ai_plan_status_text = l('ai_plan.status_card_text_profile'); ?>
<?php $ai_plan_status_focus_value = l('ai_plan.onboarding_step_1_title'); ?>
<?php if($ai_plan_current_step === 'app_review') $ai_plan_status_focus_value = l('ai_plan.onboarding_step_2_title'); ?>
<?php if($ai_plan_current_step === 'weekly') $ai_plan_status_focus_value = l('ai_plan.onboarding_step_3_title'); ?>
<?php if($ai_plan_current_step === 'plan') $ai_plan_status_focus_value = l('ai_plan.onboarding_step_4_title'); ?>
<?php if($data->is_profile_complete && !$data->is_weekly_plan_eligible) $ai_plan_status_text = $ai_growth_is_pro ? 'PRO Start je aktivan. Nakon prvih 15 klikova i prijava otključavaš puni tjedni AI plan i redovni ritam rada.' : 'Za tjedni AI plan i analizu aplikacije potreban je aktivan PRO paket.'; ?>
<?php if($data->is_profile_complete && empty($data->latest_app_review)) $ai_plan_status_text = l('ai_plan.onboarding_step_2_text'); ?>
<?php if(!empty($data->latest_app_review) && $data->is_weekly_plan_eligible && !$data->latest_weekly_checkin) $ai_plan_status_text = l('ai_plan.status_card_text_weekly'); ?>
<?php if($data->latest_weekly_checkin && !$data->latest_weekly_plan) $ai_plan_status_text = l('ai_plan.status_card_text_plan_pending'); ?>
<?php if($data->latest_weekly_plan && !$data->latest_weekly_outcome) $ai_plan_status_text = l('ai_plan.status_card_text_execute'); ?>
<?php if($data->latest_weekly_outcome) $ai_plan_status_text = l('ai_plan.status_card_text_complete'); ?>
<?php if($ai_plan_new_weekly_cycle_ready) $ai_plan_status_text = 'Prethodni ciklus je zatvoren. Novi tjedni unos je sada spreman i on je sljedeći korak.'; ?>
<?php $ai_plan_current_phase = 1; ?>
<?php $ai_plan_hero_title = l('ai_plan.onboarding_step_1_title'); ?>
<?php $ai_plan_hero_text = l('ai_plan.onboarding_step_1_text'); ?>
<?php $ai_plan_hero_status_class = 'active'; ?>
<?php $ai_plan_hero_status_label = l('ai_plan.status_profile_setup'); ?>
<?php $ai_plan_metric_unlock_value = $ai_plan_tier_label; ?>
<?php $ai_plan_metric_next_value = l('ai_plan.metric_next_after_profile'); ?>
<?php $ai_plan_metric_help_text = $ai_plan_access_summary; ?>
<?php if($data->is_profile_complete && empty($data->latest_app_review)): ?>
    <?php $ai_plan_current_phase = 2; ?>
    <?php $ai_plan_hero_title = l('ai_plan.onboarding_step_2_title'); ?>
    <?php $ai_plan_hero_text = l('ai_plan.onboarding_step_2_text'); ?>
    <?php $ai_plan_hero_status_class = 'active'; ?>
    <?php $ai_plan_hero_status_label = l('ai_plan.step_status_current'); ?>
    <?php $ai_plan_metric_unlock_value = $ai_plan_tier_label; ?>
    <?php $ai_plan_metric_next_value = l('ai_plan.onboarding_step_2_title'); ?>
    <?php $ai_plan_metric_help_text = $ai_growth_tier === 'pro_start' ? 'PRO Start sada otključava tvoju 1 početnu analizu glavne FCC aplikacije.' : $ai_plan_access_summary; ?>
<?php endif ?>
<?php if(!empty($data->latest_app_review) && !$data->latest_weekly_checkin): ?>
    <?php $ai_plan_current_phase = 3; ?>
    <?php $ai_plan_hero_title = l('ai_plan.onboarding_step_3_title'); ?>
    <?php $ai_plan_hero_text = $data->is_weekly_plan_eligible ? l('ai_plan.onboarding_step_3_text') : l('ai_plan.guide_weekly_locked_text'); ?>
    <?php $ai_plan_hero_status_class = $data->is_weekly_plan_eligible ? 'active' : 'locked'; ?>
    <?php $ai_plan_hero_status_label = $data->is_weekly_plan_eligible ? l('ai_plan.step_status_current') : l('ai_plan.step_status_locked'); ?>
    <?php $ai_plan_metric_unlock_value = $ai_plan_tier_label; ?>
    <?php $ai_plan_metric_next_value = $data->is_weekly_plan_eligible ? l('ai_plan.onboarding_step_3_title') : l('ai_plan.metric_next_after_signal'); ?>
    <?php $ai_plan_metric_help_text = $data->is_weekly_plan_eligible ? $ai_plan_access_summary : ($ai_growth_is_pro ? 'Kad skupiš 15+ klikova i prijava u 30 dana, otključavaš redovni tjedni AI ciklus.' : $ai_plan_access_summary); ?>
<?php endif ?>
<?php if($data->latest_weekly_checkin): ?>
    <?php $ai_plan_current_phase = 4; ?>
    <?php $ai_plan_hero_title = l('ai_plan.onboarding_step_4_title'); ?>
    <?php $ai_plan_hero_text = $data->latest_weekly_plan ? ($data->latest_weekly_outcome ? l('ai_plan.guide_outcome_text') : l('ai_plan.guide_plan_text')) : l('ai_plan.onboarding_step_4_text'); ?>
    <?php $ai_plan_hero_status_class = $data->latest_weekly_plan ? 'success' : ($data->weekly_is_locked ? 'locked' : 'active'); ?>
    <?php $ai_plan_hero_status_label = $data->latest_weekly_plan ? l('ai_plan.status_plan_ready') : ($data->weekly_is_locked ? l('ai_plan.weekly_status_cooldown') : l('ai_plan.weekly_status_submitted')); ?>
    <?php $ai_plan_metric_unlock_value = $ai_plan_tier_label; ?>
    <?php $ai_plan_metric_next_value = $data->weekly_next_checkin_at ? \Altum\Date::get($data->weekly_next_checkin_at, 2) : l('ai_plan.weekly_now'); ?>
    <?php $ai_plan_metric_help_text = $ai_plan_access_summary; ?>
<?php endif ?>
<?php if($ai_plan_new_weekly_cycle_ready): ?>
    <?php $ai_plan_current_phase = 4; ?>
    <?php $ai_plan_hero_title = 'Novi tjedni unos je spreman'; ?>
    <?php $ai_plan_hero_text = 'Prethodni tjedan je zatvoren. Sada prvo ispuni novi tjedni unos kako bi AI izradio sljedeći plan rada.'; ?>
    <?php $ai_plan_hero_status_class = 'active'; ?>
    <?php $ai_plan_hero_status_label = 'Spremno sada'; ?>
    <?php $ai_plan_metric_unlock_value = $ai_plan_tier_label; ?>
    <?php $ai_plan_metric_next_value = l('ai_plan.onboarding_step_3_title'); ?>
    <?php $ai_plan_metric_help_text = $ai_plan_access_summary; ?>
<?php endif ?>

<?php $ai_plan_sidebar_next_step_value = l('ai_plan.onboarding_step_1_title'); ?>
<?php $ai_plan_sidebar_next_step_text = l('ai_plan.sidebar_next_step_profile'); ?>
<?php if($ai_plan_current_step === 'app_review'): ?>
    <?php $ai_plan_sidebar_next_step_value = l('ai_plan.onboarding_step_2_title'); ?>
    <?php $ai_plan_sidebar_next_step_text = l('ai_plan.sidebar_next_step_app_review'); ?>
<?php endif ?>
<?php if($ai_plan_current_step === 'weekly'): ?>
    <?php $ai_plan_sidebar_next_step_value = l('ai_plan.onboarding_step_3_title'); ?>
    <?php $ai_plan_sidebar_next_step_text = l('ai_plan.sidebar_next_step_weekly'); ?>
<?php endif ?>
<?php if($ai_plan_current_step === 'plan'): ?>
    <?php $ai_plan_sidebar_next_step_value = $data->latest_weekly_plan && !$data->latest_weekly_outcome ? l('ai_plan.weekly_cycle_step_3_title') : l('ai_plan.onboarding_step_4_title'); ?>
    <?php $ai_plan_sidebar_next_step_text = $data->latest_weekly_plan && !$data->latest_weekly_outcome ? l('ai_plan.sidebar_next_step_outcome') : l('ai_plan.sidebar_next_step_plan'); ?>
<?php endif ?>
<?php if($ai_plan_new_weekly_cycle_ready): ?>
    <?php $ai_plan_status_focus_value = 'Novi tjedni unos'; ?>
    <?php $ai_plan_sidebar_next_step_value = 'Novi tjedni unos'; ?>
    <?php $ai_plan_sidebar_next_step_text = 'Novi unos je sada otključan. Otvori ga i iz njega odmah kreće sljedeći plan.'; ?>
<?php endif ?>

<?php $ai_plan_review_unlock_value = l('ai_plan.sidebar_ready_now'); ?>
<?php if(!$data->is_profile_complete): ?>
    <?php $ai_plan_review_unlock_value = l('ai_plan.sidebar_unlock_after_profile'); ?>
<?php elseif(!$app_review_is_accessible): ?>
    <?php $ai_plan_review_unlock_value = $ai_growth_is_pro ? 'Na 15+ klikova i prijava' : 'Samo za PRO'; ?>
<?php elseif(!empty($data->latest_app_review) && $data->app_review_is_locked): ?>
    <?php $ai_plan_review_unlock_value = sprintf(l('ai_plan.sidebar_unlock_in_days'), nr(max(0, (int) ($data->app_review_countdown_days ?? 0)))); ?>
<?php endif ?>

<?php $ai_plan_weekly_unlock_value = l('ai_plan.sidebar_ready_now'); ?>
<?php if(!$data->is_profile_complete): ?>
    <?php $ai_plan_weekly_unlock_value = l('ai_plan.sidebar_unlock_after_profile'); ?>
<?php elseif(empty($data->latest_app_review)): ?>
    <?php $ai_plan_weekly_unlock_value = l('ai_plan.sidebar_unlock_after_app_review'); ?>
<?php elseif(!$data->is_weekly_plan_eligible): ?>
    <?php $ai_plan_weekly_unlock_value = $ai_growth_is_pro ? 'Čeka 15+ klikova i prijava' : 'Samo za PRO'; ?>
<?php elseif($data->weekly_is_locked): ?>
    <?php $ai_plan_weekly_unlock_value = sprintf(l('ai_plan.sidebar_unlock_in_days'), nr(max(0, (int) ($data->weekly_countdown_days ?? 0)))); ?>
<?php endif ?>

<?php
    $ai_plan_app_review_step_status_class = $ai_plan_app_review_status;
    $ai_plan_app_review_step_status_label = l('ai_plan.step_status_' . $ai_plan_app_review_status);
    $ai_plan_app_review_step_title = '2. ' . ($data->latest_app_review ? 'Pregled plana promjena aplikacije' : 'Analiza glavne FCC aplikacije');
    $ai_plan_app_review_step_text = $data->latest_app_review
        ? 'Ovdje pregledavaš zadnju analizu i preporuke za glavnu FCC aplikaciju.'
        : 'Prvi put ovdje pokrećeš analizu glavne FCC aplikacije i dobivaš jasan plan promjena.';
    $ai_plan_app_review_step_meta_label = '';
    $ai_plan_app_review_step_meta_value = '';
    $ai_plan_app_review_step_meta_value_class = '';

    if(!$data->is_profile_complete) {
        $ai_plan_app_review_step_title = '2. Analiza glavne FCC aplikacije';
        $ai_plan_app_review_step_text = 'Ovaj korak se otključava čim spremiš osnovu i odabereš glavni fokus.';
    } elseif(!$app_review_is_accessible) {
        $ai_plan_app_review_step_status_class = 'locked';
        $ai_plan_app_review_step_status_label = $ai_growth_is_pro ? 'Čeka signal' : 'Samo za PRO';
        $ai_plan_app_review_step_title = '2. Analiza glavne FCC aplikacije';
        $ai_plan_app_review_step_text = $ai_growth_is_pro
            ? 'Početna analiza je iskorištena. Nova analiza se ponovno otključava kad skupiš 15+ klikova i prijava u zadnjih 30 dana.'
            : 'AI analiza aplikacije dostupna je unutar aktivnog PRO paketa.';
        $ai_plan_app_review_step_meta_label = 'Klikovi i prijave';
        $ai_plan_app_review_step_meta_value = nr($ai_growth_signal) . ' / ' . $ai_plan_unlock_target;
        $ai_plan_app_review_step_meta_value_class = 'is-waiting';
    } elseif($data->latest_app_review && !$data->app_review_is_locked) {
        $ai_plan_app_review_step_status_class = 'review-ready';
        $ai_plan_app_review_step_status_label = 'Dostupno sada';
        $ai_plan_app_review_step_title = '2. Pregledaj plan i izradi novu analizu';
        $ai_plan_app_review_step_text = $ai_growth_tier === 'pro_start'
            ? 'Tu pregledaš početnu analizu glavne FCC aplikacije.'
            : 'Tu pregledaš zadnje upute za promjene i odmah možeš pokrenuti novu analizu aplikacije.';
        $ai_plan_app_review_step_meta_label = 'Nova analiza';
        $ai_plan_app_review_step_meta_value = $ai_growth_tier === 'pro_start' ? '1 početna analiza' : 'Možeš pokrenuti odmah';
        $ai_plan_app_review_step_meta_value_class = 'is-highlight';
    } elseif($data->latest_app_review && $data->app_review_is_locked) {
        $ai_plan_app_review_step_status_class = 'review-waiting';
        $ai_plan_app_review_step_status_label = 'Plan spreman';
        $ai_plan_app_review_step_title = '2. Pregledaj plan promjena aplikacije';
        $ai_plan_app_review_step_text = 'Tu vidiš zadnju analizu i preporuke. Nova analiza se otključava svakih 7 dana.';
        $ai_plan_app_review_step_meta_label = 'Nova analiza dostupna';
        $ai_plan_app_review_step_meta_value = !empty($data->app_review_next_at)
            ? \Altum\Date::get($data->app_review_next_at, 1)
            : 'Čeka novi termin';
        $ai_plan_app_review_step_meta_value_class = 'is-waiting';
    } elseif($data->latest_app_review) {
        $ai_plan_app_review_step_status_class = 'review-ready';
        $ai_plan_app_review_step_status_label = 'Plan spreman';
        $ai_plan_app_review_step_title = '2. Pregledaj plan promjena aplikacije';
        $ai_plan_app_review_step_text = 'Ovdje ti je spremljen zadnji plan promjena i ulaz za novu analizu kada poželiš.';
        $ai_plan_app_review_step_meta_label = 'Zadnja analiza';
        $ai_plan_app_review_step_meta_value = !empty($data->latest_app_review['generated_at'])
            ? \Altum\Date::get($data->latest_app_review['generated_at'], 1)
            : 'Spremno sada';
    }

    $ai_plan_app_review_last_generated = !empty($data->latest_app_review['generated_at'])
        ? \Altum\Date::get($data->latest_app_review['generated_at'], 1)
        : '';

    $ai_plan_is_operational_mode = !empty($data->latest_weekly_checkin);

    $ai_plan_onboarding_step_1_status_class = !$data->is_profile_complete ? 'current' : 'done';
    $ai_plan_onboarding_step_1_status_label = !$data->is_profile_complete ? 'Sada' : 'Gotovo';
    $ai_plan_onboarding_step_2_status_class = !$data->is_profile_complete ? 'locked' : (!empty($data->latest_app_review) ? 'done' : 'current');
    $ai_plan_onboarding_step_2_status_label = !$data->is_profile_complete ? 'Čeka' : (!empty($data->latest_app_review) ? 'Gotovo' : 'Sada');
    $ai_plan_onboarding_step_3_status_class = !$data->is_profile_complete ? 'locked' : (empty($data->latest_app_review) ? 'locked' : (!$data->latest_weekly_checkin ? 'current' : 'done'));
    $ai_plan_onboarding_step_3_status_label = !$data->is_profile_complete || empty($data->latest_app_review) ? 'Čeka' : (!$data->latest_weekly_checkin ? 'Sada' : 'Gotovo');

    $ai_plan_profile_card_status_class = 'done';
    $ai_plan_profile_card_status_label = 'Profil spreman';
    $ai_plan_profile_card_title = 'Profil i smjer rada';
    $ai_plan_profile_card_text = 'Tu uređuješ cilj, publiku, fokus i smjer po kojem AI slaže ostale preporuke.';
    $ai_plan_profile_card_meta = !empty($values['updated_at']) ? \Altum\Date::get($values['updated_at'], 1) : 'Spremno sada';

    $ai_plan_review_card_status_class = !$app_review_is_accessible ? 'locked' : (!$data->app_review_is_locked ? 'review-ready' : 'review-waiting');
    $ai_plan_review_card_status_label = !$app_review_is_accessible ? ($ai_growth_is_pro ? 'Čeka signal' : 'Samo za PRO') : (!$data->app_review_is_locked ? 'Dostupno sada' : 'Pregled spreman');
    $ai_plan_review_card_title = 'Analiza FCC aplikacije';
    $ai_plan_review_card_text = !$app_review_is_accessible
        ? ($ai_growth_is_pro
            ? 'Početna analiza je iskorištena. Nova analiza se ponovno otključava na 15+ klikova i prijava u 30 dana.'
            : 'AI analiza aplikacije dostupna je samo unutar aktivnog PRO paketa.')
        : (!$data->app_review_is_locked
            ? ($ai_growth_tier === 'pro_start'
                ? 'Tu otvaraš svoju početnu AI analizu glavne aplikacije.'
                : 'Otvori zadnju analizu, pregledaj preporuke i po želji odmah pokreni novu.')
            : 'Ovdje su spremljene zadnje preporuke, a nova analiza se otključava po isteku termina.');
    $ai_plan_review_card_last = $ai_plan_app_review_last_generated ?: 'Još nema analize';
    $ai_plan_review_card_next = !$app_review_is_accessible
        ? ($ai_growth_is_pro ? (nr($ai_growth_signal) . ' / ' . $ai_plan_unlock_target . ' signala') : 'Aktiviraj PRO')
        : (!$data->app_review_is_locked
            ? ($ai_growth_tier === 'pro_start' ? '1 početna analiza' : 'Možeš pokrenuti odmah')
            : (!empty($data->app_review_next_at) ? \Altum\Date::get($data->app_review_next_at, 1) : 'Čeka novi termin'));

    $ai_plan_has_pending_outcome = !empty($data->latest_pending_outcome_plan);
    $ai_plan_weekly_card_status_class = $ai_plan_has_pending_outcome ? 'review-waiting' : ($data->latest_weekly_plan ? 'done' : 'current');
    $ai_plan_weekly_card_status_label = $ai_plan_has_pending_outcome ? 'Zatvori tjedan' : ($data->latest_weekly_plan ? 'Zadnji plan' : 'U pripremi');
    $ai_plan_weekly_card_title = 'Tjedni plan';
    $ai_plan_weekly_card_text = $ai_plan_has_pending_outcome
        ? 'Prije novog tjednog unosa ovdje prvo zatvaraš prošli tjedan i upisuješ što se stvarno dogodilo.'
        : ($data->latest_weekly_plan
            ? 'Tu otvaraš plan za ovaj ciklus, pratiš fokus tjedna i dnevne zadatke.'
            : 'Tjedni unos je spremljen. Ovdje ćeš vidjeti plan čim bude generiran.');
    $ai_plan_weekly_card_last = !empty($data->latest_weekly_plan['generated_at'])
        ? \Altum\Date::get($data->latest_weekly_plan['generated_at'], 1)
        : (!empty($data->latest_weekly_checkin['submitted_at']) ? \Altum\Date::get($data->latest_weekly_checkin['submitted_at'], 1) : 'Još nema plana');
    $ai_plan_weekly_card_next = $ai_plan_has_pending_outcome
        ? 'Prvo spremi izvještaj'
        : (!empty($data->weekly_next_checkin_at)
        ? \Altum\Date::get($data->weekly_next_checkin_at, 1)
        : 'Spremno sada');
    $ai_plan_weekly_block_url = $ai_plan_has_pending_outcome
        ? url('ai-plan?section=plan#ai-plan-weekly-outcome-start')
        : ($data->latest_weekly_plan ? $ai_plan_section_urls['plan'] : $ai_plan_section_urls['weekly']);
?>

<div class="container ai-plan-shell">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= l('ai_plan.header') ?></h1>
            <p class="text-muted mb-0"><?= l('ai_plan.subheader') ?></p>
        </div>
    </div>

    <?= \Altum\Alerts::output_alerts() ?>

    <div class="card ai-plan-card ai-plan-hero mb-4"><div class="card-body"><div class="row align-items-center"><div class="col-12 col-xl-8 mb-3 mb-xl-0"><div class="ai-plan-hero-copy"><div class="d-flex flex-wrap align-items-center mb-2" style="gap:.5rem;"><span class="ai-plan-chip active"><?= l('ai_plan.phase_label') ?> <?= $ai_plan_current_phase ?></span><span class="ai-plan-chip <?= $ai_plan_hero_status_class ?>"><?= $ai_plan_hero_status_label ?></span><?php if($data->latest_weekly_plan): ?><span class="ai-plan-chip success"><?= l('ai_plan.status_plan_ready') ?></span><?php elseif($data->weekly_is_locked && $data->latest_weekly_checkin): ?><span class="ai-plan-chip locked"><?= l('ai_plan.weekly_status_cooldown') ?></span><?php endif ?></div><h2 class="h4 mb-2"><?= $ai_plan_hero_title ?></h2><p class="text-muted mb-3"><?= $ai_plan_hero_text ?></p><?php if($data->is_profile_complete && !$data->is_weekly_plan_eligible): ?><div class="small text-muted mb-3"><?= $ai_growth_is_pro ? 'Za puni tjedni AI plan treba ti još ' . nr($ai_plan_signal_missing) . ' klikova i prijava u zadnjih 30 dana.' : 'Za AI planove i analize aplikacije aktiviraj PRO paket.' ?></div><?php endif ?><a href="<?= $ai_plan_primary_cta_url ?>" class="btn btn-primary"><?= $ai_plan_primary_cta_label ?></a></div></div><div class="col-12 col-xl-4"><div class="ai-plan-card ai-plan-hero-summary p-3 h-100"><div class="ai-plan-stat-row"><span class="text-muted small">Klikovi i prijave u 30 dana</span><strong><?= nr($ai_growth_signal) ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small">AI status</span><strong><?= $ai_plan_metric_unlock_value ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= $ai_plan_metric_next_label ?></span><strong><?= $ai_plan_metric_next_value ?></strong></div><div class="small text-muted mt-3 mb-0"><?= $ai_plan_metric_help_text ?></div></div></div></div></div></div>

            <div class="card ai-plan-card ai-plan-guide-card mb-4">
                <div class="card-body">
                    <?php if(!$ai_plan_is_operational_mode): ?>
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:.75rem;">
                            <div>
                                <h2 class="h5 mb-1">Prvi prolaz kroz sustav</h2>
                                <p class="text-muted mb-1">Prvo složi osnovu, zatim napravi analizu glavne aplikacije i pokreni prvi tjedan.</p>
                                <p class="text-muted mb-0">Kad završiš ova 3 koraka, ovdje se otvara radni pregled analiza, plana i rezultata.</p>
                            </div>
                        </div>

                        <div class="ai-plan-step-grid">
                            <a href="<?= $ai_plan_step_profile_url ?>" class="ai-plan-step-card <?= $ai_plan_onboarding_step_1_status_class ?>">
                                <span class="ai-plan-step-status <?= $ai_plan_onboarding_step_1_status_class ?>"><?= $ai_plan_onboarding_step_1_status_label ?></span>
                                <div class="font-weight-bold">1. Postavi osnovu</div>
                                <div class="text-muted small">AI prvo mora razumjeti što želiš postići, kome se obraćaš i koji ti je glavni fokus.</div>
                            </a>

                            <a href="<?= htmlspecialchars($ai_plan_step_app_review_url, ENT_QUOTES, 'UTF-8') ?>" class="ai-plan-step-card <?= $ai_plan_onboarding_step_2_status_class ?> <?= $app_review_is_accessible ? '' : 'ai-plan-disabled-link disabled' ?>"<?= $app_review_is_accessible ? '' : ' data-tooltip title="' . htmlspecialchars($app_review_locked_reason, ENT_QUOTES, 'UTF-8') . '" onclick="event.preventDefault();"' ?>>
                                <span class="ai-plan-step-status <?= $ai_plan_onboarding_step_2_status_class ?>"><?= $ai_plan_onboarding_step_2_status_label ?></span>
                                <div class="font-weight-bold">2. Analiza glavne FCC aplikacije</div>
                                <div class="text-muted small"><?= !$data->is_profile_complete ? 'Ovaj korak se otključava čim spremiš osnovu i odabereš glavni fokus.' : (!empty($data->latest_app_review) ? 'Analiza je napravljena i spremna za pregled preporuka.' : ($ai_growth_is_pro ? 'PRO Start ovdje otključava tvoju 1 početnu analizu glavne FCC aplikacije.' : 'AI analiza aplikacije dostupna je samo unutar aktivnog PRO paketa.')) ?></div>
                            </a>

                            <a href="<?= htmlspecialchars($ai_plan_step_weekly_url, ENT_QUOTES, 'UTF-8') ?>" class="ai-plan-step-card <?= $ai_plan_onboarding_step_3_status_class ?><?= $ai_plan_onboarding_step_3_status_class === 'locked' ? ' ai-plan-disabled-link disabled' : '' ?>"<?= $ai_plan_onboarding_step_3_status_class === 'locked' ? ' data-tooltip title="' . htmlspecialchars(l('ai_plan.onboarding_step_3_locked'), ENT_QUOTES, 'UTF-8') . '" onclick="event.preventDefault();"' : '' ?>>
                                <span class="ai-plan-step-status <?= $ai_plan_onboarding_step_3_status_class ?>"><?= $ai_plan_onboarding_step_3_status_label ?></span>
                                <div class="font-weight-bold">3. Pokreni prvi tjedan</div>
                                <div class="text-muted small"><?= $ai_growth_is_pro ? 'PRO Start uključuje 1 prvi tjedni plan. Nakon 15+ klikova i prijava otključavaš puni tjedni AI ciklus.' : 'Ispuni kratki tjedni unos i dobij jasan plan rada za sljedećih 7 dana.' ?></div>
                            </a>
                        </div>

                        <div class="ai-plan-lock-box mt-3">
                            <div class="font-weight-bold mb-1">Ne moraš sve odjednom</div>
                            <div class="text-muted small mb-0">Fokusiraj se samo na sljedeći korak. Kad završiš prva 3 koraka, ovdje se automatski otvara radni pregled.</div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:.75rem;">
                            <div>
                                <h2 class="h5 mb-1">Radni pregled</h2>
                                <p class="text-muted mb-1">Ovdje vidiš što je zadnje napravljeno, što možeš otvoriti sada i kada se otključava sljedeći termin.</p>
                                <p class="text-muted mb-0">Klik na blok otvara detalje ispod. Status badge gore odmah pokazuje je li nešto spremno sada ili čeka novi termin.</p>
                            </div>
                        </div>

                        <div class="ai-plan-step-grid">
                            <a href="<?= $ai_plan_section_urls['profile'] ?>" class="ai-plan-step-card <?= $ai_plan_profile_card_status_class ?>">
                                <span class="ai-plan-step-status <?= $ai_plan_profile_card_status_class ?>"><?= $ai_plan_profile_card_status_label ?></span>
                                <div class="font-weight-bold"><?= $ai_plan_profile_card_title ?></div>
                                <div class="text-muted small"><?= $ai_plan_profile_card_text ?></div>
                                <div class="ai-plan-step-meta">
                                    <div class="ai-plan-step-meta-row">
                                        <span class="ai-plan-step-meta-label">Zadnje ažuriranje</span>
                                        <span class="ai-plan-step-meta-value"><?= htmlspecialchars($ai_plan_profile_card_meta, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            </a>

                            <a href="<?= htmlspecialchars($ai_plan_step_app_review_url, ENT_QUOTES, 'UTF-8') ?>" class="ai-plan-step-card <?= $ai_plan_review_card_status_class ?>">
                                <span class="ai-plan-step-status <?= $ai_plan_review_card_status_class ?>"><?= $ai_plan_review_card_status_label ?></span>
                                <div class="font-weight-bold"><?= $ai_plan_review_card_title ?></div>
                                <div class="text-muted small"><?= $ai_plan_review_card_text ?></div>
                                <div class="ai-plan-step-meta">
                                    <div class="ai-plan-step-meta-row">
                                        <span class="ai-plan-step-meta-label">Zadnja analiza</span>
                                        <span class="ai-plan-step-meta-value"><?= htmlspecialchars($ai_plan_review_card_last, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="ai-plan-step-meta-row">
                                        <span class="ai-plan-step-meta-label">Nova analiza</span>
                                        <span class="ai-plan-step-meta-value <?= !$data->app_review_is_locked ? 'is-highlight' : 'is-waiting' ?>"><?= htmlspecialchars($ai_plan_review_card_next, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            </a>

                            <a href="<?= $ai_plan_weekly_block_url ?>" class="ai-plan-step-card <?= $ai_plan_weekly_card_status_class ?>">
                                <span class="ai-plan-step-status <?= $ai_plan_weekly_card_status_class ?>"><?= $ai_plan_weekly_card_status_label ?></span>
                                <div class="font-weight-bold"><?= $ai_plan_weekly_card_title ?></div>
                                <div class="text-muted small"><?= $ai_plan_weekly_card_text ?></div>
                                <div class="ai-plan-step-meta">
                                    <div class="ai-plan-step-meta-row">
                                        <span class="ai-plan-step-meta-label">Zadnji plan</span>
                                        <span class="ai-plan-step-meta-value"><?= htmlspecialchars($ai_plan_weekly_card_last, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="ai-plan-step-meta-row">
                                        <span class="ai-plan-step-meta-label"><?= $ai_plan_has_pending_outcome ? 'Sljedeći korak' : 'Sljedeći unos' ?></span>
                                        <span class="ai-plan-step-meta-value <?= ($ai_plan_has_pending_outcome || !empty($data->weekly_next_checkin_at)) ? 'is-waiting' : 'is-highlight' ?>"><?= htmlspecialchars($ai_plan_weekly_card_next, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="ai-plan-lock-box mt-3">
                            <div class="font-weight-bold mb-1">Sve je na jednom mjestu</div>
                            <div class="text-muted small mb-0">Blokovi su sada radni centri. Otvori željeni blok, pregledaj zadnje preporuke i pokreni novu akciju kad status pokaže da je dostupna.</div>
                        </div>
                    <?php endif ?>
                </div>
            </div>

    <div class="row">
        <div class="<?= $ai_plan_active_section === 'app_review' ? 'col-12' : 'col-12 col-xl-8' ?> mb-4 ai-plan-main-column">
            <?php if($ai_plan_active_section === 'profile'): ?>
            <div class="card ai-plan-card ai-plan-form-card ai-plan-profile-card" id="ai-plan-profile-start"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;"><div><h2 class="h5 mb-1"><?= l('ai_plan.form_title') ?></h2><p class="text-muted mb-0"><?= l('ai_plan.form_text') ?></p></div><?php if(!empty($values['updated_at'])): ?><div class="small text-muted"><?= l('ai_plan.last_updated') ?>: <?= \Altum\Date::get($values['updated_at'], 2) ?></div><?php endif ?></div>
                <form action="<?= $ai_plan_section_urls['profile'] ?>" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                    <div class="form-group"><label class="font-weight-bold d-block"><?= l('ai_plan.primary_goal') ?></label><div class="ai-plan-option-grid"><?php foreach($data->options['primary_goal'] as $option): ?><div class="ai-plan-option"><input type="radio" id="primary_goal_<?= $option ?>" name="primary_goal" value="<?= $option ?>" <?= $values['primary_goal'] === $option ? 'checked="checked"' : null ?> /><label for="primary_goal_<?= $option ?>"><?= l('ai_plan.option.primary_goal.' . $option) ?></label></div><?php endforeach ?></div><?= \Altum\Alerts::output_field_error('primary_goal') ?></div>

                    <div class="form-group"><label class="font-weight-bold d-block"><?= l('ai_plan.priority_offer') ?></label><div class="ai-plan-option-grid"><?php foreach($data->options['priority_offer'] as $option): ?><div class="ai-plan-option"><input type="radio" id="priority_offer_<?= $option ?>" name="priority_offer" value="<?= $option ?>" <?= $values['priority_offer'] === $option ? 'checked="checked"' : null ?> /><label for="priority_offer_<?= $option ?>"><?= l('ai_plan.option.priority_offer.' . $option) ?></label></div><?php endforeach ?></div><?= \Altum\Alerts::output_field_error('priority_offer') ?></div>

                    <div class="form-group"><label class="font-weight-bold d-block"><?= l('ai_plan.active_channels') ?></label><div class="ai-plan-option-grid"><?php foreach($data->options['active_channels'] as $option): ?><div class="ai-plan-option"><input type="checkbox" id="active_channels_<?= $option ?>" name="active_channels[]" value="<?= $option ?>" <?= in_array($option, $values['active_channels'], true) ? 'checked="checked"' : null ?> /><label for="active_channels_<?= $option ?>"><?= l('ai_plan.option.active_channels.' . $option) ?></label></div><?php endforeach ?></div><?= \Altum\Alerts::output_field_error('active_channels') ?></div>

                    <div class="form-row"><div class="col-12 col-md-6"><div class="form-group"><label for="available_time" class="font-weight-bold"><?= l('ai_plan.available_time') ?></label><select id="available_time" name="available_time" class="custom-select <?= \Altum\Alerts::has_field_errors('available_time') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->options['available_time'] as $option): ?><option value="<?= $option ?>" <?= $values['available_time'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.available_time.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('available_time') ?></div></div><div class="col-12 col-md-6"><div class="form-group"><label for="biggest_blocker" class="font-weight-bold"><?= l('ai_plan.biggest_blocker') ?></label><select id="biggest_blocker" name="biggest_blocker" class="custom-select <?= \Altum\Alerts::has_field_errors('biggest_blocker') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->options['biggest_blocker'] as $option): ?><option value="<?= $option ?>" <?= $values['biggest_blocker'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.biggest_blocker.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('biggest_blocker') ?></div></div></div>

                    <div class="form-row"><div class="col-12 col-md-6"><div class="form-group"><label for="communication_style" class="font-weight-bold"><?= l('ai_plan.communication_style') ?></label><select id="communication_style" name="communication_style" class="custom-select <?= \Altum\Alerts::has_field_errors('communication_style') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->options['communication_style'] as $option): ?><option value="<?= $option ?>" <?= $values['communication_style'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.communication_style.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('communication_style') ?></div></div><div class="col-12 col-md-6"><div class="form-group"><label for="follow_up_readiness" class="font-weight-bold"><?= l('ai_plan.follow_up_readiness') ?></label><select id="follow_up_readiness" name="follow_up_readiness" class="custom-select <?= \Altum\Alerts::has_field_errors('follow_up_readiness') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->options['follow_up_readiness'] as $option): ?><option value="<?= $option ?>" <?= $values['follow_up_readiness'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.follow_up_readiness.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('follow_up_readiness') ?></div></div></div>

                    <div class="form-group"><label for="weekly_change" class="font-weight-bold"><?= l('ai_plan.weekly_change') ?></label><select id="weekly_change" name="weekly_change" class="custom-select <?= \Altum\Alerts::has_field_errors('weekly_change') ? 'is-invalid' : null ?>"><option value=""><?= l('global.choose') ?></option><?php foreach($data->options['weekly_change'] as $option): ?><option value="<?= $option ?>" <?= $values['weekly_change'] === $option ? 'selected="selected"' : null ?>><?= l('ai_plan.option.weekly_change.' . $option) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('weekly_change') ?></div>

                    <div class="form-row"><div class="col-12 col-md-6"><div class="form-group"><label for="audience_focus" class="font-weight-bold"><?= l('ai_plan.audience_focus') ?></label><input id="audience_focus" type="text" name="audience_focus" class="form-control" maxlength="120" value="<?= htmlspecialchars($values['audience_focus'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= l('ai_plan.audience_focus_placeholder') ?>" /></div></div><div class="col-12 col-md-6"><div class="form-group"><label for="product_focus" class="font-weight-bold"><?= l('ai_plan.product_focus') ?></label><input id="product_focus" type="text" name="product_focus" class="form-control" maxlength="120" value="<?= htmlspecialchars($values['product_focus'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= l('ai_plan.product_focus_placeholder') ?>" /></div></div></div>

                    <div class="form-group"><label for="visual_tone_preference" class="font-weight-bold"><?= l('ai_plan.visual_tone_preference') ?></label><input id="visual_tone_preference" type="text" name="visual_tone_preference" class="form-control" maxlength="160" value="<?= htmlspecialchars($values['visual_tone_preference'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= l('ai_plan.visual_tone_preference_placeholder') ?>" /></div>

                    <div class="form-group mb-0"><label for="notes" class="font-weight-bold"><?= l('ai_plan.notes') ?></label><textarea id="notes" name="notes" class="form-control" rows="4" maxlength="1000" placeholder="<?= l('ai_plan.notes_placeholder') ?>"><?= htmlspecialchars($values['notes'], ENT_QUOTES, 'UTF-8') ?></textarea></div>

                    <div class="mt-4 d-flex flex-wrap align-items-center" style="gap:.75rem;"><button type="submit" name="save_profile" value="1" class="btn btn-primary"><i class="fas fa-save fa-sm mr-1"></i> <?= l('ai_plan.save') ?></button><span class="text-muted small"><?= l('ai_plan.form_footer') ?></span></div>
                </form>
            </div></div>

            <?= $render_app_review_teaser_card($app_review_page_url, (bool) $data->is_profile_complete, $app_review_is_accessible, $app_review_locked_reason, $data->latest_app_review ?? null, $data->app_review_quality_payload ?? [], $ai_growth_access, (bool) $data->app_review_is_locked, $data->app_review_next_at ?? null, false, $app_review_editor_actions) ?>
            <?php endif ?>

            <?php if($ai_plan_active_section === 'app_review'): ?>
            <?php
                $ai_plan_app_review_partial = __DIR__ . '/partials/app-review-section.php';
                if(file_exists($ai_plan_app_review_partial)) {
                    require $ai_plan_app_review_partial;
                } else {
                    echo '<div class="card ai-plan-card ai-plan-tool-card"><div class="card-body"><div class="ai-plan-history-empty"><div class="font-weight-bold mb-1">'
                        . htmlspecialchars(l('ai_plan.optional_tool_title'), ENT_QUOTES, 'UTF-8')
                        . '</div><div class="small mb-0">'
                        . htmlspecialchars('App Review sekcija trenutačno nije dostupna. Osvježi deploy paket i provjeri da je uključen AI Plan partial za App Review.', ENT_QUOTES, 'UTF-8')
                        . '</div></div></div></div>';
                }
            ?>
            <?php endif ?>

            <?php if($ai_plan_active_section === 'weekly'): ?>
            <?php
                $weekly_requires_closing_report = !empty($data->latest_pending_outcome_plan);
                $weekly_is_first_cycle = empty($data->latest_weekly_checkin);
                $weekly_history_active_plan = $data->display_weekly_plan ?? null;
                $weekly_history_active_outcome = $data->display_weekly_outcome ?? null;
                $weekly_history_active_generated_at = (string) ($data->plan_active_generated_at ?? '');
                $weekly_history_is_explicitly_closed = !empty($data->plan_history_only);

                if(!$weekly_history_is_explicitly_closed && $weekly_requires_closing_report && empty($weekly_history_active_plan)) {
                    $weekly_history_active_plan = $data->latest_pending_outcome_plan ?? null;
                    $weekly_history_active_outcome = $data->latest_pending_outcome ?? null;
                    $weekly_history_active_generated_at = (string) ($data->latest_pending_outcome_plan['generated_at'] ?? '');
                } elseif(!$weekly_history_is_explicitly_closed && empty($weekly_history_active_plan)) {
                    $weekly_history_active_plan = $data->latest_weekly_plan ?? null;
                    $weekly_history_active_outcome = $data->latest_weekly_outcome ?? null;
                    $weekly_history_active_generated_at = (string) ($data->latest_weekly_plan['generated_at'] ?? '');
                }

                $weekly_cycle_steps = [
                    [
                        'status' => $data->latest_weekly_checkin ? 'done' : (($data->is_profile_complete_for_weekly && !empty($data->latest_app_review) && $data->is_weekly_plan_eligible && !$data->weekly_is_locked) ? 'current' : 'locked'),
                        'title' => l('ai_plan.weekly_cycle_step_1_title'),
                        'text' => l('ai_plan.weekly_cycle_step_1_text'),
                    ],
                    [
                        'status' => $data->latest_weekly_plan ? 'done' : ($data->latest_weekly_checkin ? 'current' : 'locked'),
                        'title' => l('ai_plan.weekly_cycle_step_2_title'),
                        'text' => l('ai_plan.weekly_cycle_step_2_text'),
                    ],
                    [
                        'status' => $data->latest_weekly_outcome ? 'done' : ($data->latest_weekly_plan ? 'current' : 'locked'),
                        'title' => l('ai_plan.weekly_cycle_step_3_title'),
                        'text' => l('ai_plan.weekly_cycle_step_3_text'),
                    ],
                ];
                $weekly_cycle_next_value = l('ai_plan.weekly_cycle_next_fill');
                if($weekly_requires_closing_report) $weekly_cycle_next_value = l('ai_plan.weekly_cycle_next_record');
                if($data->latest_weekly_checkin && !$data->latest_weekly_plan) $weekly_cycle_next_value = l('ai_plan.weekly_cycle_next_wait_plan');
                if($data->latest_weekly_outcome) $weekly_cycle_next_value = l('ai_plan.weekly_cycle_next_done');
            ?>
            <div class="card ai-plan-card ai-plan-form-card ai-plan-weekly-card"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;"><div><h2 class="h5 mb-1"><?= l('ai_plan.weekly_title') ?></h2><p class="text-muted mb-0"><?= $weekly_is_first_cycle ? l('ai_plan.weekly_text') : 'Prethodni ciklus je zatvoren. U ovom unosu postavljaš fokus za sljedećih 7 dana. Ako želiš, prije plana možeš prvo promijeniti Profil i smjer rada.' ?></p></div><?php if(!empty($data->latest_weekly_checkin['submitted_at'])): ?><div class="small text-muted"><?= l('ai_plan.last_updated') ?>: <?= \Altum\Date::get($data->latest_weekly_checkin['submitted_at'], 2) ?></div><?php endif ?></div>

                <?= $weekly_is_first_cycle ? $render_weekly_cycle_panel($weekly_cycle_steps, $weekly_cycle_next_value) : $render_weekly_refresh_panel($ai_plan_section_urls['profile']) ?>

                <?php if(!$data->is_profile_complete_for_weekly): ?>
                    <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.weekly_locked_profile_title') ?></div><div class="text-muted small mb-0"><?= l('ai_plan.weekly_locked_profile') ?></div></div>
                <?php elseif(empty($data->latest_app_review)): ?>
                    <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.onboarding_step_3_locked_title') ?></div><div class="text-muted small mb-0"><?= l('ai_plan.onboarding_step_3_locked') ?></div></div>
                <?php elseif(!$data->is_weekly_plan_eligible): ?>
                    <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.weekly_locked_signal_title') ?></div><div class="text-muted small mb-0"><?= sprintf(l('ai_plan.weekly_locked_signal'), 15, nr($ai_growth_signal)) ?></div></div>
                <?php elseif($weekly_requires_closing_report): ?>
                <?php elseif($data->weekly_is_locked): ?>
                    <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.weekly_locked_cooldown_title') ?></div><div class="text-muted small mb-0"><?= sprintf(l('ai_plan.weekly_locked_cooldown_short'), $data->weekly_countdown_days ?? 0) ?></div></div>
                <?php else: ?>
                    <?php if(!$weekly_is_first_cycle && !empty($data->latest_weekly_outcome)): ?>
                        <?= $render_weekly_outcome_summary(
                            $data->latest_weekly_outcome,
                            'Sažetak prošlotjednog izvještaja',
                            'Prije ovog novog unosa ovdje vidiš što je spremljeno iz prošlog ciklusa. AI taj sažetak koristi za sljedeći plan.'
                        ) ?>
                    <?php endif ?>

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

                <?= $render_weekly_plan_history_strip(
                    (array) ($data->weekly_plans ?? []),
                    $data->latest_weekly_plan ?? null,
                    $weekly_history_active_plan,
                    $weekly_history_active_outcome,
                    $weekly_history_active_generated_at,
                    (array) ($data->feedback_loop_payload ?? []),
                    $render_weekly_plan_result,
                    'weekly'
                ) ?>

                <?php if($weekly_requires_closing_report): ?>
                    <div class="ai-plan-review-highlight mb-3">
                        <div class="font-weight-bold mb-2">Prvo zatvori prošli tjedan</div>
                        <div class="text-muted small mb-0">Prije novog tjednog unosa trebaš kratko upisati što se stvarno dogodilo. Tek nakon toga otvara se sljedeći tjedni ciklus.</div>
                    </div>

                    <?= $render_weekly_outcome_form(
                        $ai_plan_section_urls['plan'],
                        $data->latest_pending_outcome ?? null,
                        'Nakon spremanja izvještaja otvara se novi tjedni unos za sljedeći plan.',
                        $data->latest_pending_outcome_plan ?? null,
                        [
                            'selected_link_id' => (int) ($data->app_review_selected_link_id ?? 0),
                            'app_review_generated_at' => (string) (($data->selected_app_review['generated_at'] ?? $data->latest_app_review['generated_at'] ?? '')),
                            'app_review_review_key' => (string) (($data->selected_app_review['review_key'] ?? $data->selected_app_review['generated_at'] ?? $data->latest_app_review['review_key'] ?? $data->latest_app_review['generated_at'] ?? '')),
                        ]
                    ) ?>
                <?php endif ?>
            </div></div>

            <?= $render_app_review_teaser_card($app_review_page_url, (bool) $data->is_profile_complete, $app_review_is_accessible, $app_review_locked_reason, $data->latest_app_review ?? null, $data->app_review_quality_payload ?? [], $ai_growth_access, (bool) $data->app_review_is_locked, $data->app_review_next_at ?? null, true, $app_review_editor_actions) ?>
            <?php endif ?>

            <?php if($ai_plan_active_section === 'plan'): ?>
            <?php $display_weekly_plan = $data->display_weekly_plan ?? $data->latest_weekly_plan; ?>
            <?php $display_weekly_outcome = $data->display_weekly_outcome ?? $data->latest_weekly_outcome; ?>
            <?php
                $plan_requires_closing_report = !empty($data->latest_pending_outcome_plan) && (($display_weekly_plan['generated_at'] ?? '') === ($data->latest_pending_outcome_plan['generated_at'] ?? ''));
                $plan_cycle_steps = [
                    [
                        'status' => $data->latest_weekly_checkin ? 'done' : 'locked',
                        'title' => l('ai_plan.weekly_cycle_step_1_title'),
                        'text' => l('ai_plan.weekly_cycle_step_1_text'),
                    ],
                    [
                        'status' => $display_weekly_plan ? 'done' : ($data->latest_weekly_checkin ? 'current' : 'locked'),
                        'title' => l('ai_plan.weekly_cycle_step_2_title'),
                        'text' => l('ai_plan.weekly_cycle_step_2_text'),
                    ],
                    [
                        'status' => $display_weekly_outcome ? 'done' : ($display_weekly_plan ? 'current' : 'locked'),
                        'title' => l('ai_plan.weekly_cycle_step_3_title'),
                        'text' => l('ai_plan.weekly_cycle_step_3_text'),
                    ],
                ];
                $plan_cycle_next_value = !$display_weekly_plan ? l('ai_plan.weekly_cycle_next_wait_plan') : (!$display_weekly_outcome ? l('ai_plan.weekly_cycle_next_record') : l('ai_plan.weekly_cycle_next_done'));
            ?>
            <div class="card ai-plan-card"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap:.75rem;"><div><h2 class="h5 mb-1"><?= l('ai_plan.plan_title') ?></h2><p class="text-muted mb-0"><?= l('ai_plan.plan_text') ?></p></div><?php if($display_weekly_plan): ?><div class="small text-muted"><?= l('ai_plan.plan_generated_at') ?>: <?= \Altum\Date::get($display_weekly_plan['generated_at'], 2) ?></div><?php endif ?></div>

                <?= $render_weekly_cycle_panel($plan_cycle_steps, $plan_cycle_next_value) ?>

                <?= $render_weekly_plan_history_strip(
                    (array) ($data->weekly_plans ?? []),
                    $data->latest_weekly_plan ?? null,
                    $display_weekly_plan,
                    ($plan_requires_closing_report ? ($data->latest_pending_outcome ?? null) : $display_weekly_outcome),
                    (string) ($data->plan_active_generated_at ?? ''),
                    (array) ($data->feedback_loop_payload ?? []),
                    $render_weekly_plan_result,
                    'plan'
                ) ?>

                <?php if(empty($data->latest_app_review)): ?>
                    <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.onboarding_step_4_locked_title') ?></div><div class="text-muted small mb-0"><?= l('ai_plan.onboarding_step_4_locked') ?></div></div>
                <?php elseif(!$data->latest_weekly_checkin): ?>
                    <div class="ai-plan-lock-box"><div class="font-weight-bold mb-2"><?= l('ai_plan.plan_empty_title') ?></div><div class="text-muted small mb-0"><?= l('ai_plan.plan_empty_text') ?></div></div>
                <?php elseif(!$display_weekly_plan && empty($data->plan_history_only)): ?>
                    <div class="ai-plan-lock-box mb-3"><div class="font-weight-bold mb-2"><?= l('ai_plan.plan_pending_title') ?></div><div class="text-muted small mb-0"><?= l('ai_plan.plan_pending_text') ?></div></div>
                <?php elseif(empty($data->weekly_plans)): ?>
                    <?= $render_weekly_plan_result(
                        $display_weekly_plan,
                        $display_weekly_outcome,
                        (array) ($data->feedback_loop_payload ?? []),
                        !empty($data->feedback_loop_payload['has_feedback']) && (($display_weekly_plan['generated_at'] ?? '') === ($data->latest_weekly_plan['generated_at'] ?? ''))
                    ) ?>
                <?php endif ?>

                <?php if($plan_requires_closing_report): ?>
                    <div class="ai-plan-review-highlight mb-3" id="ai-plan-weekly-outcome-start">
                        <div class="font-weight-bold mb-2">Prvo zatvori prošli tjedan</div>
                        <div class="text-muted small mb-0">Prije novog tjednog unosa ovdje kratko upiši što je stvarno prošlo, što te kočilo i što mijenjaš dalje. Nakon spremanja izvještaja otvara ti se novi tjedni unos.</div>
                    </div>

                    <?= $render_weekly_outcome_form(
                        $ai_plan_section_urls['plan'],
                        $data->latest_pending_outcome ?? null,
                        'Nakon spremanja izvještaja otvara se novi tjedni unos za sljedeći plan.',
                        $data->latest_pending_outcome_plan ?? null,
                        [
                            'selected_link_id' => (int) ($data->app_review_selected_link_id ?? 0),
                            'app_review_generated_at' => (string) (($data->selected_app_review['generated_at'] ?? $data->latest_app_review['generated_at'] ?? '')),
                            'app_review_review_key' => (string) (($data->selected_app_review['review_key'] ?? $data->selected_app_review['generated_at'] ?? $data->latest_app_review['review_key'] ?? $data->latest_app_review['generated_at'] ?? '')),
                        ]
                    ) ?>
                <?php endif ?>
            </div></div>

            <?php if($display_weekly_plan && !$plan_requires_closing_report): ?>
                <?= $render_weekly_outcome_form(
                    $ai_plan_section_urls['plan'],
                    $display_weekly_outcome,
                    l('ai_plan.outcome_footer'),
                    $display_weekly_plan,
                    [
                        'selected_link_id' => (int) ($data->app_review_selected_link_id ?? 0),
                        'app_review_generated_at' => (string) (($data->selected_app_review['generated_at'] ?? $data->latest_app_review['generated_at'] ?? '')),
                        'app_review_review_key' => (string) (($data->selected_app_review['review_key'] ?? $data->selected_app_review['generated_at'] ?? $data->latest_app_review['review_key'] ?? $data->latest_app_review['generated_at'] ?? '')),
                    ]
                ) ?>
            <?php endif ?>
            <?php endif ?>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const openLabel = <?= json_encode(l('ai_plan.app_review_preview_open')) ?>;

                    const initAppPreview = function(config) {
                        const appSelect = document.getElementById(config.selectId);
                        if(!appSelect) {
                            return;
                        }

                        const previewTitle = document.getElementById(config.titleId);
                        const previewUrl = document.getElementById(config.urlId);
                        const previewOpen = document.getElementById(config.openId);
                        const previewFrame = document.getElementById(config.frameId);
                        const previewWrap = document.getElementById(config.wrapId);
                        const previewEmpty = document.getElementById(config.emptyId);

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
                                    const previewTargetUrl = publicUrl + (publicUrl.indexOf('?') === -1 ? '?' : '&') + 'fcc_preview_ts=' + Date.now();
                                    previewFrame.setAttribute('src', 'about:blank');
                                    previewFrame.title = appLabel;

                                    window.setTimeout(function() {
                                        previewFrame.setAttribute('src', previewTargetUrl);
                                    }, 30);
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

                        appSelect.addEventListener('change', function() {
                            updatePreview();

                            if(config.navigateOnChange) {
                                const selectedValue = appSelect.value || '';
                                const pageUrl = appSelect.dataset.pageUrl || '';

                                if(pageUrl && selectedValue) {
                                    window.location.href = pageUrl + '?app_review_selected_link_id=' + encodeURIComponent(selectedValue);
                                }
                            }
                        });
                        updatePreview();
                    };

                    initAppPreview({
                        selectId: 'app_review_selected_link_id_weekly',
                        titleId: 'ai-plan-preview-title-weekly',
                        urlId: 'ai-plan-preview-url-weekly',
                        openId: 'ai-plan-preview-open-weekly',
                        frameId: 'ai-plan-preview-frame-weekly',
                        wrapId: 'ai-plan-preview-frame-wrap-weekly',
                        emptyId: 'ai-plan-preview-empty-weekly'
                    });

                    const aiPlanAjaxUrl = (typeof url !== 'undefined' ? url : <?= json_encode(url()) ?>) + 'link-ajax';
                    const aiPlanAjaxToken = <?= json_encode(\Altum\Csrf::get()) ?>;

                    const renderAiPlanEditorNotice = function(container, message, status) {
                        if(!container) {
                            return;
                        }

                        container.innerHTML = '';

                        if(!message) {
                            return;
                        }

                        const notice = document.createElement('div');
                        const noticeClass = status === 'success'
                            ? 'is-success'
                            : (status === 'warning' ? 'is-warning' : 'is-error');
                        notice.className = 'ai-plan-tool-teaser-notice ' + noticeClass;
                        notice.textContent = message;
                        container.appendChild(notice);
                    };

                    document.querySelectorAll('.js-ai-plan-editor-action').forEach(function(button) {
                        button.addEventListener('click', function(event) {
                            event.preventDefault();

                            if(button.disabled) {
                                return;
                            }

                            const requestType = button.getAttribute('data-request-type') || '';
                            const linkId = button.getAttribute('data-link-id') || '';
                            const notificationTarget = button.getAttribute('data-notification-target') || '';
                            const notificationContainer = notificationTarget ? document.querySelector(notificationTarget) : null;
                            const actionGroup = button.closest('.ai-plan-tool-teaser-actions');
                            const actionButtons = actionGroup ? Array.from(actionGroup.querySelectorAll('.js-ai-plan-editor-action')) : [button];

                            renderAiPlanEditorNotice(notificationContainer, '', 'success');

                            if((button.getAttribute('data-ai-stale') || '0') === '1') {
                                renderAiPlanEditorNotice(notificationContainer, button.getAttribute('data-ai-stale-message') || <?= json_encode(l('link.settings.ai_bundle_stale_notice')) ?>, 'warning');
                            }

                            if(!requestType || !linkId) {
                                renderAiPlanEditorNotice(notificationContainer, <?= json_encode(l('global.error_message.basic')) ?>, 'error');
                                return;
                            }

                            actionButtons.forEach(function(actionButton) {
                                actionButton.setAttribute('disabled', 'disabled');
                            });

                            const payload = new URLSearchParams();
                            payload.append('token', aiPlanAjaxToken);
                            payload.append('request_type', requestType);
                            payload.append('link_id', linkId);

                            fetch(aiPlanAjaxUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: payload.toString(),
                                credentials: 'same-origin',
                            })
                                .then(function(response) {
                                    return response.json().catch(function() {
                                        return null;
                                    });
                                })
                                .then(function(response) {
                                    if(!response || !response.status) {
                                        renderAiPlanEditorNotice(notificationContainer, <?= json_encode(l('global.error_message.basic')) ?>, 'error');
                                        return;
                                    }

                                    renderAiPlanEditorNotice(notificationContainer, response.message || '', response.status);

                                    if(response.status === 'success') {
                                        window.setTimeout(function() {
                                            window.location.reload();
                                        }, 850);
                                    } else {
                                        actionButtons.forEach(function(actionButton) {
                                            actionButton.removeAttribute('disabled');
                                        });
                                    }
                                })
                                .catch(function() {
                                    renderAiPlanEditorNotice(notificationContainer, <?= json_encode(l('global.error_message.basic')) ?>, 'error');
                                    actionButtons.forEach(function(actionButton) {
                                        actionButton.removeAttribute('disabled');
                                    });
                                });
                        });
                    });

                    document.querySelectorAll('.js-ai-plan-weekly-outcome-form').forEach(function(form) {
                        const submitButton = form.querySelector('.js-ai-plan-weekly-outcome-submit');
                        const requiredFields = Array.from(form.querySelectorAll('[data-ai-required="1"]'));

                        if(!submitButton || !requiredFields.length) {
                            return;
                        }

                        const isFieldFilled = function(field) {
                            return ((field.value || '').trim() !== '');
                        };

                        const syncOutcomeSubmitState = function() {
                            const isReady = requiredFields.every(isFieldFilled);
                            submitButton.disabled = !isReady;
                        };

                        requiredFields.forEach(function(field) {
                            ['input', 'change', 'keyup'].forEach(function(eventName) {
                                field.addEventListener(eventName, syncOutcomeSubmitState);
                            });
                        });

                        form.addEventListener('submit', function(event) {
                            syncOutcomeSubmitState();

                            if(submitButton.disabled) {
                                event.preventDefault();
                            }
                        });

                        syncOutcomeSubmitState();
                    });
                });
            </script>
        </div>

        <?php if($ai_plan_active_section !== 'app_review'): ?>
        <div class="col-12 col-xl-4 mb-4">
            <div class="ai-plan-sticky-column">
            <div class="card ai-plan-card ai-plan-side-card mb-3"><div class="card-body"><h2 class="h5 mb-2"><?= l('ai_plan.sidebar_status_title') ?></h2><div class="small mb-3"><?= $ai_plan_status_text ?></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.status_card_focus_label') ?></span><strong><?= htmlspecialchars($ai_plan_status_focus_value, ENT_QUOTES, 'UTF-8') ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.sidebar_next_step_label') ?></span><strong><?= htmlspecialchars($ai_plan_sidebar_next_step_value, ENT_QUOTES, 'UTF-8') ?></strong></div><div class="small text-muted mt-3"><?= $ai_plan_sidebar_next_step_text ?></div><a href="<?= $ai_plan_primary_cta_url ?>" class="btn btn-primary btn-block mt-3"><?= $ai_plan_primary_cta_label ?></a></div></div>
            <div class="card ai-plan-card ai-plan-side-card mb-3"><div class="card-body"><h2 class="h5 mb-2"><?= l('ai_plan.sidebar_unlock_title') ?></h2><div class="small mb-3"><?= l('ai_plan.sidebar_unlock_text') ?></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.sidebar_unlock_app_review_label') ?></span><strong><?= htmlspecialchars($ai_plan_review_unlock_value, ENT_QUOTES, 'UTF-8') ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.sidebar_unlock_weekly_label') ?></span><strong><?= htmlspecialchars($ai_plan_weekly_unlock_value, ENT_QUOTES, 'UTF-8') ?></strong></div></div></div>
            <div class="card ai-plan-card ai-plan-side-card mb-3"><div class="card-body"><h2 class="h5 mb-2"><?= l('ai_plan.weekly_status_title') ?></h2><?php if($data->latest_weekly_checkin): ?><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= l('ai_plan.last_updated') ?></div><div class="font-weight-bold"><?= \Altum\Date::get($data->latest_weekly_checkin['submitted_at'], 2) ?></div></div><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= l('ai_plan.weekly_priority') ?></div><div><?= l('ai_plan.option.weekly_priority.' . $data->latest_weekly_checkin['weekly_priority']) ?></div></div><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= l('ai_plan.ai_need') ?></div><div><?= l('ai_plan.option.ai_need.' . $data->latest_weekly_checkin['ai_need']) ?></div></div><?php if(!empty($data->latest_weekly_checkin['adaptive_question_key']) && !empty($data->latest_weekly_checkin['adaptive_answer'])): ?><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= l('ai_plan.weekly_adaptive_title') ?></div><div class="text-muted small mb-1"><?= l('ai_plan.adaptive_question.' . $data->latest_weekly_checkin['adaptive_question_key']) ?></div><div class="small mb-0"><?= htmlspecialchars($data->latest_weekly_checkin['adaptive_answer'], ENT_QUOTES, 'UTF-8') ?></div></div><?php endif ?><?php if(!empty($data->latest_weekly_checkin['weekly_context'])): ?><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= l('ai_plan.weekly_context') ?></div><div class="text-muted small mb-0"><?= htmlspecialchars($data->latest_weekly_checkin['weekly_context'], ENT_QUOTES, 'UTF-8') ?></div></div><?php endif ?><?php else: ?><div class="text-muted small mb-0"><?= l('ai_plan.weekly_empty') ?></div><?php endif ?></div></div>
            <div class="card ai-plan-card ai-plan-side-card mb-3"><div class="card-body"><h2 class="h5 mb-2"><?= l('ai_plan.analytics_title') ?></h2><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.analytics_top_source') ?></span><strong><?= htmlspecialchars($data->analytics_payload['top_source_label'], ENT_QUOTES, 'UTF-8') ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.analytics_top_medium') ?></span><strong><?= htmlspecialchars($data->analytics_payload['top_medium_label'], ENT_QUOTES, 'UTF-8') ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.analytics_top_country') ?></span><strong><?= htmlspecialchars($data->analytics_payload['top_country_label'], ENT_QUOTES, 'UTF-8') ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.analytics_top_device') ?></span><strong><?= htmlspecialchars($data->analytics_payload['top_device_label'], ENT_QUOTES, 'UTF-8') ?></strong></div><div class="ai-plan-stat-row"><span class="text-muted small"><?= l('ai_plan.analytics_funnel') ?></span><strong><?= sprintf(l('ai_plan.analytics_funnel_value'), nr($data->analytics_payload['funnel']['active_funnels'] ?? 0), nr($data->analytics_payload['funnel']['total_funnels'] ?? 0)) ?></strong></div><div class="small text-muted mt-3 mb-0"><?= l('ai_plan.analytics_help') ?></div></div></div>
            <div class="card ai-plan-card ai-plan-side-card mb-3"><div class="card-body"><h2 class="h5 mb-2"><?= l('ai_plan.signal_summary.title') ?></h2><?php foreach(($data->signal_summary_payload ?? []) as $signal_item): ?><div class="ai-plan-history-item"><div class="small text-muted mb-1"><?= htmlspecialchars((string) ($signal_item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div><div class="small mb-0"><?= htmlspecialchars((string) ($signal_item['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div><?php endforeach ?></div></div>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>
<!-- /Custom code: FC-2026-03-31 -->
