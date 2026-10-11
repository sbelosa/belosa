<?php defined('ALTUMCODE') || die();
$fp_tools = fcc_partner_tools();
$fp_tool_flags = ['pixels'=>'pixels_is_enabled','domains'=>'domains_is_enabled','projects'=>'projects_is_enabled','splash-pages'=>'splash_page_is_enabled'];
foreach($fp_tool_flags as $key=>$flag) if(empty(settings()->links->{$flag})) unset($fp_tools[$key]);
if(empty(settings()->codes->qr_codes_is_enabled)) unset($fp_tools['qr-codes']);
?>
<section class="fp-panel fp-tool-library" id="tools" aria-labelledby="fp-tools-title">
    <span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('TVOJ RADNI PROSTOR'), ENT_QUOTES, 'UTF-8') ?></span>
    <h2 id="fp-tools-title"><?= htmlspecialchars(fcc_t('Alati za tvoje poslovanje'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="fp-muted"><?= htmlspecialchars(fcc_t('Odaberi što želiš napraviti. Svaki alat koristi tvoj postojeći račun i podatke.'), ENT_QUOTES, 'UTF-8') ?></p>
    <label class="sr-only" for="fp-tool-search"><?= htmlspecialchars(fcc_t('Pronađi alat'), ENT_QUOTES, 'UTF-8') ?></label>
    <input type="search" id="fp-tool-search" class="fp-tool-search" placeholder="<?= htmlspecialchars(fcc_t('Pronađi alat, npr. QR kod ili poveznica'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
    <?php foreach(fcc_partner_tool_groups() as $group=>$label): ?>
    <div class="fp-tool-group" data-tool-group><h3><?= $h($label) ?></h3><div class="fp-tool-grid">
        <?php foreach($fp_tools as $key=>$tool): if($tool['group'] !== $group) continue;
            $tool_pro = in_array($key,['vip-funnel-studio','funnels-analytics'],true);
            $tool_locked = $key === 'funnels-analytics' && (empty($this->user->plan_settings->enabled_biolink_blocks->lead_funnel) || empty($this->user->plan_settings->statistics));
        ?>
        <a class="fp-tool-item" data-tool-item <?php if($tool_pro): ?><?= fcc_pro_link((int)$this->user->user_id,'funnel',$tool_locked?fcc_pro_upgrade_path('funnel'):$tool['path']) ?><?php else: ?>href="<?= url($tool['path']) ?>"<?php endif ?>>
            <span class="fp-tool-icon" aria-hidden="true"><?= fcc_partner_icon($tool['icon']) ?></span>
            <span><strong><?= $h($tool['title']) ?><?php if($tool_pro): ?> <span class="fcc-pro-badge"><?= htmlspecialchars(fcc_t('PRO'), ENT_QUOTES, 'UTF-8') ?></span><?php endif ?></strong><small><?= $h($tool['description']) ?></small></span>
            <span aria-hidden="true">→</span>
        </a>
        <?php endforeach ?>
    </div></div>
    <?php endforeach ?>
    <p class="fp-muted" data-tool-empty hidden><?= htmlspecialchars(fcc_t('Nema tog alata. Pokušaj s drugim nazivom.'), ENT_QUOTES, 'UTF-8') ?></p>
    <p class="fp-tool-plan-note"><?= htmlspecialchars(fcc_t('Dostupne mogućnosti ovise o tvojem paketu.'), ENT_QUOTES, 'UTF-8') ?></p>
</section>
<details class="fp-panel fp-tools-library"><summary><?= htmlspecialchars(fcc_t('Materijali, podrška i račun'), ENT_QUOTES, 'UTF-8') ?></summary>
<div class="fp-admin-links">
<?php foreach([[fcc_t('Materijali uz moj program'),'partner?view=materials'],[fcc_t('Članci i proizvodi'),'partner/share'],[fcc_t('Moj AI savjetnik'),'fcc-ai'],[fcc_t('Moj Forever i CC bodovi'),'forever-business'],[fcc_t('Moj napredak'),'partner?view=progress'],[fcc_t('Pomoć i moji zahtjevi'),'feedback-tickets'],[fcc_t('Moje uplate i računi'),'account-payments']] as [$label,$path]): ?><a href="<?= url($path) ?>"><?= $h($label) ?><span>→</span></a><?php endforeach ?>
<?php foreach(settings()->content->pages_is_enabled?(new \Altum\Models\Page())->get_pages('top'):[] as $page): ?><a href="<?= $h($page->url) ?>" <?= $page->target==='_blank'?'target="_blank" rel="noopener"':'' ?>><?= $h($page->title) ?><span>→</span></a><?php endforeach ?>
</div></details>
