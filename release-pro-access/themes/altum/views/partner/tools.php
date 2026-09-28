<?php defined('ALTUMCODE') || die();
$fp_tools = fcc_partner_tools();
$fp_tool_flags = ['pixels'=>'pixels_is_enabled','domains'=>'domains_is_enabled','projects'=>'projects_is_enabled','splash-pages'=>'splash_page_is_enabled'];
foreach($fp_tool_flags as $key=>$flag) if(empty(settings()->links->{$flag})) unset($fp_tools[$key]);
if(empty(settings()->codes->qr_codes_is_enabled)) unset($fp_tools['qr-codes']);
?>
<section class="fp-panel fp-tool-library" id="tools" aria-labelledby="fp-tools-title">
    <span class="fp-eyebrow">TVOJ RADNI PROSTOR</span>
    <h2 id="fp-tools-title">Alati za tvoje poslovanje</h2>
    <p class="fp-muted">Odaberi što želiš napraviti. Svaki alat koristi tvoj postojeći račun i podatke.</p>
    <label class="sr-only" for="fp-tool-search">Pronađi alat</label>
    <input type="search" id="fp-tool-search" class="fp-tool-search" placeholder="Pronađi alat, npr. piksel ili domena" autocomplete="off">
    <?php foreach(fcc_partner_tool_groups() as $group=>$label): ?>
    <div class="fp-tool-group" data-tool-group><h3><?= $h($label) ?></h3><div class="fp-tool-grid">
        <?php foreach($fp_tools as $key=>$tool): if($tool['group'] !== $group) continue;
            $tool_pro = in_array($key,['vip-funnel-studio','funnels-analytics'],true);
            $tool_locked = $key === 'funnels-analytics' && (empty($this->user->plan_settings->enabled_biolink_blocks->lead_funnel) || empty($this->user->plan_settings->statistics));
        ?>
        <a class="fp-tool-item" data-tool-item <?php if($tool_pro): ?><?= fcc_pro_link((int)$this->user->user_id,'funnel',$tool_locked?fcc_pro_upgrade_path('funnel'):$tool['path']) ?><?php else: ?>href="<?= url($tool['path']) ?>"<?php endif ?>>
            <span class="fp-tool-icon" aria-hidden="true"><?= fcc_partner_icon($tool['icon']) ?></span>
            <span><strong><?= $h($tool['title']) ?><?php if($tool_pro): ?> <span class="fcc-pro-badge">PRO</span><?php endif ?></strong><small><?= $h($tool['description']) ?></small></span>
            <span aria-hidden="true">→</span>
        </a>
        <?php endforeach ?>
    </div></div>
    <?php endforeach ?>
    <p class="fp-muted" data-tool-empty hidden>Nema tog alata. Pokušaj s drugim nazivom.</p>
    <p class="fp-tool-plan-note">Dostupne mogućnosti ovise o tvojem paketu.</p>
</section>
<details class="fp-panel fp-tools-library"><summary>Materijali, podrška i račun</summary>
<div class="fp-admin-links">
<?php foreach([['Materijali uz moj program','partner?view=materials'],['Članci i proizvodi','partner/share'],['AI savjetnici i upiti','fcc-ai'],['AI pregled kartice','ai-app-review'],['Moj Forever i CC bodovi','forever-business'],['Moj napredak','partner?view=progress'],['Pomoć i moji zahtjevi','feedback-tickets'],['Moje uplate i računi','account-payments']] as [$label,$path]): ?><a href="<?= url($path) ?>"><?= $h($label) ?><span>→</span></a><?php endforeach ?>
<?php foreach(settings()->content->pages_is_enabled?(new \Altum\Models\Page())->get_pages('top'):[] as $page): ?><a href="<?= $h($page->url) ?>" <?= $page->target==='_blank'?'target="_blank" rel="noopener"':'' ?>><?= $h($page->title) ?><span>→</span></a><?php endforeach ?>
</div></details>
