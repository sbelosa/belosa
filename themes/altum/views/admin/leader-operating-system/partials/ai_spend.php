<?php
 defined('ALTUMCODE') || die();
 $spend=$md['ai_spend']??[];
 $money=static fn($amount)=>$amount===null?'Nema procjene':number_format((float)$amount,2,',','.').' USD';
 $month=$spend['month']??[];
?>
<section class="los-panel" id="los-ai-spend" aria-labelledby="los-ai-spend-title" style="margin-top:18px">
 <div class="los-row" style="justify-content:space-between"><div><div class="los-eyebrow">Trošak i mjesečna kontrola</div><h2 id="los-ai-spend-title">AI potrošnja suradnika</h2></div><span class="los-tag">Približno, u USD</span></div>
 <p class="los-muted">COACH obuhvaća razgovore i pomoć uz poruke. AI na karticama obuhvaća javne savjetnike ovog suradnika. Pregled prikazuje proteklih 7, 30 i 90 dana do sada, neovisno o filtru ostalih aktivnosti. Potvrđeni povezani računi dijele trošak i mjesečne pragove.</p>
 <?php if(empty($spend['available'])): ?>
 <div class="los-note los-warning">Potrošnja trenutačno nije dostupna. Nedostajući podatak ne znači da nije bilo troška.</div>
 <?php else: ?>
 <?php
  $rows=[];
  foreach([7,30,90] as $days){
   $period=$spend['periods'][$days]??[];
   $cell=static function($channel)use($period,$money,$e){$v=$period[$channel]??[];return '<strong>'.$e($money($v['usd']??null)).'</strong>'.(!empty($v['partial'])?'<small>Djelomična procjena</small>':'');};
   $rows[]=[$e($days.' dana'),$cell('coach'),$cell('cards'),$cell('total')];
  }
  $table(['Razdoblje','COACH','AI na karticama','Ukupno'],$rows,true);
 ?>
 <p class="los-muted" style="margin-top:12px">Procjena koristi standardne cijene modela i spremljene tokene. U starijim zapisima bez podataka o predmemoriji koristi se oprezna procjena. Promotivni krediti i popusti mogu smanjiti stvarni račun. Raniji mali COACH upiti bez spremljenih tokena nisu uključeni, a lokalni odgovori bez API poziva nemaju trošak.</p>
 <h3>Ovaj mjesec: <?= $e($month['label']??'') ?></h3>
 <div class="los-grid">
 <?php foreach(['coach'=>'COACH','cards'=>'AI na karticama'] as $channel=>$label): $status=$spend['channels'][$channel]??[];$limit=(float)($status['limit_usd']??0);$spent=$status['spent_usd']??null;$budgetSpent=$channel==='coach'?($status['premium_spent_usd']??$spent):$spent;$used=$limit>0&&$budgetSpent!==null?min(100,100*(float)$budgetSpent/$limit):0; ?>
 <div class="los-panel los-soft">
  <div class="los-row" style="justify-content:space-between"><strong><?= $e($label) ?></strong><span class="los-tag"><?= !empty($status['downgraded'])?'Štedljivi način':'Redovni način' ?></span></div>
  <p style="margin-top:12px"><strong><?= $e($money($budgetSpent)) ?></strong> od <?= $limit>0?$e($money($limit)):'praga koji je isključen' ?><?= $channel==='coach'?' za redovni model':'' ?></p>
  <?php if($channel==='coach'): ?><small>Ukupni COACH trošak ovog mjeseca, uključujući Lunu: <?= $e($money($spent)) ?>.</small><?php endif ?>
  <?php if($limit>0): ?><div role="progressbar" aria-label="<?= $e($label.' iskorištenje mjesečnog praga') ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= round($used,1) ?>" style="height:8px;background:#e5ebe7;border-radius:8px;overflow:hidden"><div style="height:100%;width:<?= round($used,1) ?>%;background:<?= !empty($status['downgraded'])?'#a06818':'#287252' ?>"></div></div><?php endif ?>
  <small style="margin-top:10px;display:block"><?= !empty($status['ledger_unknown'])?'Trošak jednog API pokušaja nije potvrđen. Trenutačno se koristi štedljivi model Luna.':(!empty($status['downgraded'])?'Sljedeći AI upiti koriste štedljivi model Luna do početka novog mjeseca.':($limit>0?'Nakon dosezanja praga sljedeći upiti prelaze na štedljivi model Luna.':'Prag je isključen. Redovni model ostaje dostupan.')) ?></small>
  <?php if(!empty($status['partial'])): ?><small>Dio potrošnje nema potpunu evidenciju.</small><?php endif ?>
 </div>
 <?php endforeach ?>
 </div>
 <p class="los-muted" style="margin-top:12px">Novi mjesečni obračun počinje <?= $e(isset($month['reset_at'])?(new DateTimeImmutable($month['reset_at']))->format('d. m. Y. H:i'):'prvog dana sljedećeg mjeseca') ?>, prema vremenu u Zagrebu. Razgovor ostaje dostupan i nakon dosezanja praga. Ovo je prag za promjenu modela, zato ukupni trošak može nastaviti rasti uz jeftiniji model.</p>
 <details class="los-advanced">
  <summary>Promijeni mjesečne pragove za ovog suradnika</summary>
  <form method="post" action="<?= $link() ?>#los-ai-spend" style="margin-top:14px">
   <?= $token ?><input type="hidden" name="los_action" value="ai_limits"><input type="hidden" name="member" value="<?= (int)$p['id'] ?>">
   <div class="los-grid">
    <?php foreach(['coach'=>'Mjesečni prag za COACH na redovnom modelu, USD','cards'=>'Mjesečni prag za AI na karticama, USD'] as $channel=>$label): $override=$spend['overrides'][$channel]??null; ?>
    <label><?= $e($label) ?><input type="number" inputmode="decimal" min="0" max="10000" step="0.01" name="<?= $channel ?>_limit_usd" value="<?= $override===null?'':$e(number_format((float)$override,2,'.','')) ?>" placeholder="Zadani prag: <?= $e($spend['defaults'][$channel]??0) ?> USD"></label>
    <?php endforeach ?>
   </div>
   <p class="los-muted">Prazno polje koristi zadani prag, <?= $e($money($spend['defaults']['coach']??8)) ?> za COACH i <?= $e($money($spend['defaults']['cards']??10)) ?> za kartice. Vrijednost 0 isključuje prag za odabranu vrstu. Povećanje praga odmah vraća redovni model ako je mjesečna potrošnja ispod novog praga.</p>
   <button class="los-btn primary">Spremi mjesečne pragove</button>
  </form>
 </details>
 <?php endif ?>
</section>
