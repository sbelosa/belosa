<?php defined('ALTUMCODE') || die() ?>

<?php
$e = static function($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$funnels = is_array($data->funnels ?? null) ? $data->funnels : [];
$templates = is_array($data->import_templates ?? null) ? $data->import_templates : [];
$total_views = 0;
$total_leads = 0;

foreach($funnels as $item) {
    $analytics = is_array($item['analytics'] ?? null) ? $item['analytics'] : [];
    $total_views += (int) ($analytics['views'] ?? 0);
    $total_leads += (int) ($analytics['leads'] ?? 0);
}
?>

<?= \Altum\Alerts::output_alerts() ?>

<style>
    .vip-funnel-center {
        --vf-center-bg: #0a111d;
        --vf-center-panel: #111b2a;
        --vf-center-panel-soft: #162235;
        --vf-center-border: rgba(255,255,255,.08);
        --vf-center-text: #eef4ff;
        --vf-center-muted: rgba(238,244,255,.68);
        --vf-center-accent: #67d8c9;
        color: var(--vf-center-text);
        padding-bottom: 3rem;
    }

    .vip-funnel-center a:hover {
        text-decoration: none;
    }

    .vip-funnel-center-hero,
    .vip-funnel-center-panel,
    .vip-funnel-center-card,
    .vip-funnel-center-template {
        border: 1px solid var(--vf-center-border);
        background: linear-gradient(180deg, rgba(255,255,255,.045), rgba(255,255,255,.02)), var(--vf-center-panel);
        box-shadow: 0 1.2rem 2.4rem rgba(2,8,23,.22);
    }

    .vip-funnel-center-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: center;
        border-radius: 1.35rem;
        padding: 1.35rem;
        margin-bottom: 1rem;
    }

    .vip-funnel-center-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .38rem .72rem;
        border-radius: 999px;
        background: rgba(103,216,201,.12);
        color: #d8fffb;
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .vip-funnel-center-title {
        margin: .75rem 0 .35rem;
        color: #fff;
        font-size: clamp(1.8rem, 3vw, 2.55rem);
        line-height: 1.05;
        font-weight: 900;
    }

    .vip-funnel-center-copy {
        max-width: 62rem;
        color: var(--vf-center-muted);
        line-height: 1.65;
        margin: 0;
    }

    .vip-funnel-center-actions,
    .vip-funnel-center-card-actions,
    .vip-funnel-center-url-row {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        align-items: center;
    }

    .vip-funnel-center-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        min-height: 2.75rem;
        padding: .72rem 1rem;
        border-radius: .9rem;
        border: 1px solid rgba(255,255,255,.1);
        background: rgba(255,255,255,.05);
        color: var(--vf-center-text);
        font-weight: 800;
        cursor: pointer;
    }

    .vip-funnel-center-btn:hover {
        color: #fff;
        border-color: rgba(103,216,201,.32);
    }

    .vip-funnel-center-btn-primary {
        background: var(--vf-center-accent);
        border-color: var(--vf-center-accent);
        color: #06121d;
    }

    .vip-funnel-center-btn-primary:hover {
        color: #06121d;
    }

    .vip-funnel-center-btn-danger {
        background: rgba(255,88,112,.12);
        border-color: rgba(255,88,112,.38);
        color: #ffd7de;
    }

    .vip-funnel-center-btn-danger:hover {
        border-color: rgba(255,88,112,.72);
        color: #fff;
    }

    .vip-funnel-center-btn:disabled {
        cursor: not-allowed;
        opacity: .52;
    }

    .vip-funnel-center-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .8rem;
        margin-bottom: 1rem;
    }

    .vip-funnel-center-kpi {
        min-height: 7.4rem;
        border-radius: 1rem;
        padding: 1rem;
        border: 1px solid var(--vf-center-border);
        background: rgba(255,255,255,.035);
    }

    .vip-funnel-center-kpi-label {
        color: var(--vf-center-muted);
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .vip-funnel-center-kpi-value {
        margin-top: .55rem;
        color: #fff;
        font-size: 2rem;
        line-height: 1;
        font-weight: 900;
    }

    .vip-funnel-center-panel {
        border-radius: 1.2rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .vip-funnel-center-section-head {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .vip-funnel-center-section-title {
        color: #fff;
        font-size: 1.05rem;
        font-weight: 900;
        margin: 0 0 .25rem;
    }

    .vip-funnel-center-section-sub {
        color: var(--vf-center-muted);
        margin: 0;
        font-size: .9rem;
        line-height: 1.55;
    }

    .vip-funnel-center-list {
        display: grid;
        gap: .85rem;
    }

    .vip-funnel-center-card {
        border-radius: 1.05rem;
        padding: 1rem;
    }

    .vip-funnel-center-card-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: .8rem;
        align-items: flex-start;
        margin-bottom: .85rem;
    }

    .vip-funnel-center-card-title {
        margin: 0;
        color: #fff;
        font-size: 1.05rem;
        line-height: 1.25;
        font-weight: 900;
    }

    .vip-funnel-center-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .45rem;
    }

    .vip-funnel-center-chip {
        display: inline-flex;
        align-items: center;
        padding: .28rem .58rem;
        border-radius: 999px;
        background: rgba(255,255,255,.07);
        color: rgba(238,244,255,.76);
        font-size: .72rem;
        font-weight: 800;
    }

    .vip-funnel-center-url-row {
        padding: .7rem;
        border-radius: .85rem;
        background: rgba(0,0,0,.16);
        border: 1px solid rgba(255,255,255,.06);
        margin-bottom: .85rem;
    }

    .vip-funnel-center-url {
        flex: 1 1 20rem;
        min-width: 0;
        border: 0;
        background: transparent;
        color: var(--vf-center-text);
        font-size: .88rem;
    }

    .vip-funnel-center-url:focus {
        outline: 0;
    }

    .vip-funnel-center-mini-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .55rem;
        margin-bottom: .9rem;
    }

    .vip-funnel-center-mini-kpi {
        border-radius: .85rem;
        padding: .7rem;
        background: rgba(255,255,255,.045);
        border: 1px solid rgba(255,255,255,.055);
    }

    .vip-funnel-center-mini-kpi span {
        display: block;
        color: var(--vf-center-muted);
        font-size: .72rem;
        font-weight: 800;
    }

    .vip-funnel-center-mini-kpi strong {
        display: block;
        margin-top: .25rem;
        color: #fff;
        font-size: 1.05rem;
    }

    .vip-funnel-center-template-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .85rem;
    }

    .vip-funnel-center-template {
        border-radius: 1.05rem;
        padding: 1rem;
    }

    .vip-funnel-center-empty {
        border-radius: 1rem;
        border: 1px dashed rgba(255,255,255,.16);
        color: var(--vf-center-muted);
        padding: 1.2rem;
        text-align: center;
    }

    .vip-funnel-delete-modal {
        position: fixed;
        inset: 0;
        z-index: 1080;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .vip-funnel-delete-modal.is-open {
        display: flex;
    }

    .vip-funnel-delete-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(2,8,23,.78);
        backdrop-filter: blur(10px);
    }

    .vip-funnel-delete-dialog {
        position: relative;
        width: min(100%, 34rem);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 1.25rem;
        background: linear-gradient(180deg, rgba(255,255,255,.07), rgba(255,255,255,.025)), #111827;
        box-shadow: 0 2rem 5rem rgba(0,0,0,.44);
        padding: 1.15rem;
        color: var(--vf-center-text);
    }

    .vip-funnel-delete-close {
        position: absolute;
        top: .8rem;
        right: .8rem;
        width: 2.25rem;
        height: 2.25rem;
        border: 1px solid rgba(255,255,255,.1);
        border-radius: .75rem;
        background: rgba(255,255,255,.05);
        color: var(--vf-center-text);
    }

    .vip-funnel-delete-icon {
        display: inline-flex;
        width: 3rem;
        height: 3rem;
        align-items: center;
        justify-content: center;
        border-radius: 1rem;
        background: rgba(255,88,112,.14);
        color: #ff8fa2;
        margin-bottom: .9rem;
    }

    .vip-funnel-delete-title {
        margin: 0 2.4rem .5rem 0;
        color: #fff;
        font-size: 1.35rem;
        font-weight: 900;
    }

    .vip-funnel-delete-copy {
        color: var(--vf-center-muted);
        line-height: 1.65;
        margin-bottom: .9rem;
    }

    .vip-funnel-delete-warning {
        border: 1px solid rgba(255,88,112,.24);
        border-radius: .95rem;
        background: rgba(255,88,112,.09);
        padding: .85rem;
        color: #ffe4e8;
        font-weight: 800;
        margin-bottom: .9rem;
    }

    .vip-funnel-delete-check {
        display: flex;
        gap: .65rem;
        align-items: flex-start;
        padding: .85rem;
        border: 1px solid rgba(255,255,255,.08);
        border-radius: .95rem;
        background: rgba(255,255,255,.045);
        color: var(--vf-center-muted);
        line-height: 1.45;
        cursor: pointer;
        margin-bottom: 1rem;
    }

    .vip-funnel-delete-check input {
        margin-top: .2rem;
    }

    body.vip-funnel-delete-open {
        overflow: hidden;
    }

    @media (max-width: 991px) {
        .vip-funnel-center-hero,
        .vip-funnel-center-card-top,
        .vip-funnel-center-section-head {
            grid-template-columns: 1fr;
        }

        .vip-funnel-center-grid,
        .vip-funnel-center-mini-kpis,
        .vip-funnel-center-template-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="vip-funnel-center">
    <section class="vip-funnel-center-hero">
        <div>
            <div class="vip-funnel-center-eyebrow"><i class="fas fa-fw fa-diagram-project"></i> VIP Funnel 2.0 centar</div>
            <h1 class="vip-funnel-center-title">Svi funnel-i na jednom mjestu</h1>
            <p class="vip-funnel-center-copy">Ovdje biraš koji funnel uređuješ, kopiraš javni URL, ulaziš u pregled i importiraš gotov FCC VIP funnel koji kasnije samo prilagodiš svom tekstu, videu, mentorstvu i ponudi.</p>
        </div>

        <div class="vip-funnel-center-actions">
            <a href="<?= $e($data->analytics_url ?? url('funnels-analytics')) ?>" class="vip-funnel-center-btn">
                <i class="fas fa-fw fa-chart-line"></i> Analitika
            </a>
        </div>
    </section>

    <div class="vip-funnel-center-grid">
        <div class="vip-funnel-center-kpi">
            <div class="vip-funnel-center-kpi-label">Ukupno funnel-a</div>
            <div class="vip-funnel-center-kpi-value"><?= nr(count($funnels)) ?></div>
        </div>
        <div class="vip-funnel-center-kpi">
            <div class="vip-funnel-center-kpi-label">Pregledi</div>
            <div class="vip-funnel-center-kpi-value"><?= nr($total_views) ?></div>
        </div>
        <div class="vip-funnel-center-kpi">
            <div class="vip-funnel-center-kpi-label">Leadovi</div>
            <div class="vip-funnel-center-kpi-value"><?= nr($total_leads) ?></div>
        </div>
    </div>

    <section class="vip-funnel-center-panel">
        <div class="vip-funnel-center-section-head">
            <div>
                <h2 class="vip-funnel-center-section-title">Moji funnel-i</h2>
                <p class="vip-funnel-center-section-sub">Svaki funnel ima svoj javni URL i može se zasebno odabrati u FCC App bloku.</p>
            </div>
            <form method="post" class="vip-funnel-center-actions">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                <input type="hidden" name="create_vip_funnel" value="1" />
                <input type="text" name="funnel_name" class="form-control" maxlength="120" placeholder="Naziv novog funnel-a" style="min-width:16rem;" />
                <button type="submit" class="vip-funnel-center-btn vip-funnel-center-btn-primary">
                    <i class="fas fa-fw fa-plus"></i> Novi funnel
                </button>
            </form>
        </div>

        <?php if(empty($funnels)): ?>
            <div class="vip-funnel-center-empty">Još nema spremljenih funnel-a. Kreiraj prvi funnel ili importiraj gotov FCC VIP predložak.</div>
        <?php else: ?>
            <div class="vip-funnel-center-list">
                <?php foreach($funnels as $item): ?>
                    <?php
                    $row = $item['row'];
                    $analytics = is_array($item['analytics'] ?? null) ? $item['analytics'] : [];
                    $public_url = (string) ($item['public_url'] ?? '');
                    ?>
                    <article class="vip-funnel-center-card">
                        <div class="vip-funnel-center-card-top">
                            <div>
                                <h3 class="vip-funnel-center-card-title"><?= $e($row->name ?? 'VIP Funnel 2.0') ?></h3>
                                <div class="vip-funnel-center-meta">
                                    <span class="vip-funnel-center-chip">ID <?= (int) ($row->vip_funnel_id ?? 0) ?></span>
                                    <span class="vip-funnel-center-chip"><?= $e($row->status ?? 'draft') ?></span>
                                    <span class="vip-funnel-center-chip">/<?= $e($row->slug ?? '') ?></span>
                                </div>
                            </div>
	                            <div class="vip-funnel-center-card-actions">
	                                <a href="<?= $e($item['edit_url'] ?? '#') ?>" class="vip-funnel-center-btn vip-funnel-center-btn-primary">
	                                    <i class="fas fa-fw fa-pen"></i> Uredi
	                                </a>
	                                <a href="<?= $e($public_url) ?>" target="_blank" rel="noopener" class="vip-funnel-center-btn">
	                                    <i class="fas fa-fw fa-arrow-up-right-from-square"></i> Otvori
	                                </a>
	                                <button
	                                    type="button"
	                                    class="vip-funnel-center-btn vip-funnel-center-btn-danger"
	                                    data-vip-funnel-delete-open
	                                    data-funnel-id="<?= (int) ($row->vip_funnel_id ?? 0) ?>"
	                                    data-funnel-name="<?= $e($row->name ?? 'VIP Funnel 2.0') ?>"
	                                >
	                                    <i class="fas fa-fw fa-trash-alt"></i> Obriši
	                                </button>
	                            </div>
	                        </div>

                        <div class="vip-funnel-center-url-row">
                            <input class="vip-funnel-center-url" type="text" readonly value="<?= $e($public_url) ?>" data-vip-funnel-copy-source />
                            <button type="button" class="vip-funnel-center-btn" data-vip-funnel-copy>
                                <i class="fas fa-fw fa-copy"></i> Kopiraj URL
                            </button>
                        </div>

                        <div class="vip-funnel-center-mini-kpis">
                            <div class="vip-funnel-center-mini-kpi"><span>Pregledi</span><strong><?= nr((int) ($analytics['views'] ?? 0)) ?></strong></div>
                            <div class="vip-funnel-center-mini-kpi"><span>Submits</span><strong><?= nr((int) ($analytics['submits'] ?? 0)) ?></strong></div>
                            <div class="vip-funnel-center-mini-kpi"><span>Leadovi</span><strong><?= nr((int) ($analytics['leads'] ?? 0)) ?></strong></div>
                            <div class="vip-funnel-center-mini-kpi"><span>Lead rate</span><strong><?= nr((float) ($analytics['lead_rate'] ?? 0), 2) ?>%</strong></div>
                        </div>

                        <div class="vip-funnel-center-card-actions">
                            <a href="<?= $e($item['analytics_url'] ?? '#') ?>" class="vip-funnel-center-btn">
                                <i class="fas fa-fw fa-chart-simple"></i> Analitika funnel-a
                            </a>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>

    <section class="vip-funnel-center-panel">
        <div class="vip-funnel-center-section-head">
            <div>
                <h2 class="vip-funnel-center-section-title">Import FCC VIP funnel-a</h2>
                <p class="vip-funnel-center-section-sub">Predložak se importira kao novi kompletan funnel u HR ili ENG varijanti. Nakon importa otvara se editor i možeš promijeniti tekstove, video, CTA gumbe i logiku.</p>
            </div>
        </div>

        <div class="vip-funnel-center-template-grid">
            <?php foreach($templates as $template): ?>
                <article class="vip-funnel-center-template">
                    <div class="vip-funnel-center-meta mb-2">
                        <span class="vip-funnel-center-chip"><?= $e($template['badge'] ?? 'Demo') ?></span>
                        <?php if(!empty($template['recommended'])): ?>
                            <span class="vip-funnel-center-chip">Preporučeno</span>
                        <?php endif ?>
                    </div>
                    <h3 class="vip-funnel-center-card-title mb-2"><?= $e($template['name'] ?? '') ?></h3>
                    <?php if(!empty($template['goal'])): ?>
                        <div class="vip-funnel-center-card-meta mb-2">
                            <i class="fas fa-fw fa-bullseye"></i> <?= $e($template['goal']) ?>
                        </div>
                    <?php endif ?>
                    <p class="vip-funnel-center-section-sub mb-3"><?= $e($template['description'] ?? '') ?></p>
                    <div class="vip-funnel-center-card-actions">
                        <?php foreach((array) ($template['languages'] ?? ['hr' => 'HR']) as $language_key => $language_label): ?>
                            <form method="post" class="m-0">
                                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                                <input type="hidden" name="import_vip_funnel_template" value="1" />
                                <input type="hidden" name="template_key" value="<?= $e($template['key'] ?? '') ?>" />
                                <input type="hidden" name="template_language" value="<?= $e($language_key) ?>" />
                                <button type="submit" class="vip-funnel-center-btn <?= $language_key === 'hr' ? 'vip-funnel-center-btn-primary' : '' ?>">
                                    <i class="fas fa-fw fa-file-import"></i> Import <?= $e($language_label) ?>
                                </button>
                            </form>
                        <?php endforeach ?>
                    </div>
                </article>
            <?php endforeach ?>
	        </div>
	    </section>

	    <div class="vip-funnel-delete-modal" id="vip_funnel_delete_modal" aria-hidden="true">
	        <div class="vip-funnel-delete-backdrop" data-vip-funnel-delete-close></div>
	        <div class="vip-funnel-delete-dialog" role="dialog" aria-modal="true" aria-labelledby="vip_funnel_delete_title">
	            <button type="button" class="vip-funnel-delete-close" data-vip-funnel-delete-close aria-label="Zatvori">
	                <i class="fas fa-fw fa-times"></i>
	            </button>
	            <div class="vip-funnel-delete-icon">
	                <i class="fas fa-fw fa-triangle-exclamation"></i>
	            </div>
	            <h2 class="vip-funnel-delete-title" id="vip_funnel_delete_title">Trajno obrisati funnel?</h2>
	            <p class="vip-funnel-delete-copy">
	                Funnel <strong data-vip-funnel-delete-name>VIP Funnel 2.0</strong> bit će uklonjen iz tvog popisa, javni URL više neće otvarati taj funnel, a njegova Funnel analitika se briše.
	            </p>
	            <div class="vip-funnel-delete-warning">
	                Ova radnja se ne može poništiti nakon brisanja. Kontakti i demo zapisi ostaju spremljeni u pregledima.
	            </div>
	            <form method="post" id="vip_funnel_delete_form">
	                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
	                <input type="hidden" name="delete_vip_funnel" value="1" />
	                <input type="hidden" name="delete_funnel_id" value="" data-vip-funnel-delete-id />

	                <label class="vip-funnel-delete-check">
	                    <input type="checkbox" name="delete_funnel_confirmed" value="1" data-vip-funnel-delete-confirm />
	                    <span>Razumijem da brišem funnel trajno i da se ova radnja ne može vratiti.</span>
	                </label>

	                <div class="vip-funnel-center-card-actions justify-content-end">
	                    <button type="button" class="vip-funnel-center-btn" data-vip-funnel-delete-close>Odustani</button>
	                    <button type="submit" class="vip-funnel-center-btn vip-funnel-center-btn-danger" data-vip-funnel-delete-submit disabled>
	                        <i class="fas fa-fw fa-trash-alt"></i> Trajno obriši
	                    </button>
	                </div>
	            </form>
	        </div>
	    </div>

</div>
<script>
    'use strict';

    document.querySelectorAll('[data-vip-funnel-copy]').forEach(button => {
        button.addEventListener('click', async () => {
            const row = button.closest('.vip-funnel-center-url-row');
            const input = row ? row.querySelector('[data-vip-funnel-copy-source]') : null;
            if(!input) return;

            try {
                await navigator.clipboard.writeText(input.value);
                button.innerHTML = '<i class="fas fa-fw fa-check"></i> Kopirano';
                setTimeout(() => button.innerHTML = '<i class="fas fa-fw fa-copy"></i> Kopiraj URL', 1400);
            } catch(error) {
                input.focus();
                input.select();
            }
        });
    });

        const deleteModal = document.getElementById('vip_funnel_delete_modal');
        const deleteName = deleteModal ? deleteModal.querySelector('[data-vip-funnel-delete-name]') : null;
        const deleteIdInput = deleteModal ? deleteModal.querySelector('[data-vip-funnel-delete-id]') : null;
        const deleteConfirm = deleteModal ? deleteModal.querySelector('[data-vip-funnel-delete-confirm]') : null;
        const deleteSubmit = deleteModal ? deleteModal.querySelector('[data-vip-funnel-delete-submit]') : null;

        const closeDeleteModal = () => {
            if(!deleteModal) return;

            deleteModal.classList.remove('is-open');
            deleteModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('vip-funnel-delete-open');
        };

        document.querySelectorAll('[data-vip-funnel-delete-open]').forEach(button => {
            button.addEventListener('click', () => {
                if(!deleteModal || !deleteIdInput || !deleteName || !deleteConfirm || !deleteSubmit) return;

                deleteIdInput.value = button.getAttribute('data-funnel-id') || '';
                deleteName.textContent = button.getAttribute('data-funnel-name') || 'VIP Funnel 2.0';
                deleteConfirm.checked = false;
                deleteSubmit.disabled = true;
                deleteModal.classList.add('is-open');
                deleteModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('vip-funnel-delete-open');
                deleteConfirm.focus();
            });
        });

        document.querySelectorAll('[data-vip-funnel-delete-close]').forEach(button => {
            button.addEventListener('click', closeDeleteModal);
        });

        if(deleteConfirm && deleteSubmit) {
            deleteConfirm.addEventListener('change', () => {
                deleteSubmit.disabled = !deleteConfirm.checked;
            });
        }

        document.addEventListener('keydown', event => {
            if(event.key === 'Escape' && deleteModal && deleteModal.classList.contains('is-open')) {
                closeDeleteModal();
            }
        });
</script>
