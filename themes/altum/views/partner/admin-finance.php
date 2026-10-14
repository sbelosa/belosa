<?php
/* Custom code: FC-2026-10-14: Administrator finance dashboard from the FCC billing ledger. */
defined('ALTUMCODE') || die();
if(!\Altum\Authentication::is_admin()) return;
$fh = static function($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$ft = static function(string $text): string { return function_exists('fcc_t') ? fcc_t($text) : $text; };
$f = is_array($data->finance ?? null) ? $data->finance : [];
$filters = $f['filters'] ?? [];
$currency = strtoupper((string)($filters['currency'] ?? 'EUR'));
$months = (int)($filters['months'] ?? 12);
$selected_month = (string)($filters['selected_month'] ?? '');
$state = (string)($filters['state'] ?? '');
$search = (string)($filters['search'] ?? '');
$digits = max(0, min(4, (int)($f['minor_digits'] ?? 2)));
$fm = static function($minor) use ($digits, $currency): string { return number_format((int)$minor / (10 ** $digits), $digits, ',', '.') . ' ' . $currency; };
$fn = static function($count): string { return number_format((int)$count, 0, ',', '.'); };
$fd = static function($value): string {
    if(!$value) return 'Nije zabilježeno';
    try { $date = is_numeric($value) ? new \DateTimeImmutable('@' . (int)$value) : new \DateTimeImmutable((string)$value, new \DateTimeZone('UTC')); return $date->setTimezone(new \DateTimeZone('Europe/Zagreb'))->format('d. m. Y. H:i'); }
    catch(\Throwable $exception) { return 'Nije zabilježeno'; }
};
$flink = static function(array $changes = []) use ($filters): string {
    $query = ['finance' => 1, 'months' => (int)($filters['months'] ?? 12), 'currency' => (string)($filters['currency'] ?? 'EUR'), 'selected_month' => (string)($filters['selected_month'] ?? ''), 'state' => (string)($filters['state'] ?? ''), 'search' => (string)($filters['search'] ?? ''), 'page' => 1];
    foreach($changes as $key => $value) $query[$key] = $value;
    return url('partner/admin?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
};
$states = [
    '' => 'Svi računi', 'pro_access' => 'PRO pristup', 'ongoing_subscriptions' => 'Pretplate za nastavak', 'renewing_paid' => 'Plaćene uz obnovu', 'trial' => 'Proba prema FCC evidenciji', 'payment_risk' => 'Problem s naplatom', 'nonrenewing' => 'Bez iduće obnove', 'access_revoked' => 'Pristup ukinut', 'manual_granted' => 'PRO bez pretplate', 'unknown_subscription' => 'Pretplata za provjeru', 'inactive' => 'Bez aktivnog PRO pristupa',
];
$processor_statuses = ['active'=>'Aktivna', 'trialing'=>'Probno razdoblje', 'past_due'=>'Naplata kasni', 'unpaid'=>'Neplaćena', 'canceled'=>'Otkazana', 'incomplete'=>'Nedovršena', 'incomplete_expired'=>'Istekla nedovršena pretplata', 'paused'=>'Pauzirana'];
$reasons = ['insufficient_funds' => 'Nedovoljno sredstava', 'card_declined' => 'Kartica odbijena', 'expired_card' => 'Kartica je istekla', 'incorrect_cvc' => 'Netočan sigurnosni kod', 'authentication_required' => 'Potrebna potvrda plaćanja', 'do_not_honor' => 'Banka je odbila plaćanje', 'processing_error' => 'Pogreška u obradi', 'lost_card' => 'Kartica prijavljena kao izgubljena', 'stolen_card' => 'Kartica prijavljena kao ukradena', 'unknown' => 'Razlog nije zabilježen'];
$reason_label = static function($code) use ($reasons): string { $code = (string)$code; return $reasons[$code] ?? ($code !== '' ? 'Drugi zabilježeni razlog' : 'Razlog nije zabilježen'); };
$error = (string)($data->finance_error ?? $f['error'] ?? '');
?>
<div class="fp-page fp-section-admin fp-finance">
    <header class="ff-heading">
        <div><h1><?= $fh($ft('Financije')) ?></h1><p><?= $fh($ft('PRO računi, prihodi i naplata na jednom mjestu.')) ?></p></div>
        <a class="ff-button ff-button-secondary" href="<?= $fh(url('partner/admin')) ?>"><?= $fh($ft('Administratorski pregled')) ?></a>
    </header>
    <?php if(empty($f['available']) || $error !== ''): ?>
    <section class="ff-surface ff-empty ff-error" role="alert">
        <h2><?= $fh($ft('Financijski podaci trenutačno nisu dostupni.')) ?></h2>
        <p><?= $fh($ft('Pregled nije učitan pa iznose i broj računa ne možemo pouzdano prikazati. Osvježi pregled i, ako se problem ponovi, provjeri izvor naplate.')) ?></p>
        <a class="ff-button" href="<?= $fh($flink()) ?>"><?= $fh($ft('Ponovno učitaj pregled')) ?></a>
    </section>
    <?php else:
    $summary = $f['summary'] ?? [];
    $monthly = $f['monthly'] ?? [];
    $totals = $f['totals'] ?? [];
    $selected = $f['selected_month_totals'] ?? (($f['monthly'] ?? []) ? $f['monthly'][array_key_last($f['monthly'])] : []);
    $risk_age = $f['risk_age'] ?? [];
    $due = $f['next_due'] ?? [];
    $accounts = $f['accounts'] ?? [];
    $pagination = $f['pagination'] ?? [];
    $current_month = (new \DateTimeImmutable((string)($f['generated_at'] ?? 'now'), new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('Europe/Zagreb'))->format('Y-m');
    $display_month = $selected_month !== '' ? $selected_month : $current_month;
    $month_label = $selected['label'] ?? $display_month;
    foreach($monthly as $row) if(($row['month'] ?? '') === $display_month) $month_label = (string)($row['label'] ?? $display_month);
    $completed = array_values(array_filter($monthly, static function($row) use ($current_month): bool { return (string)($row['month'] ?? '') < $current_month; }));
    usort($completed, static function($a, $b): int { return strcmp((string)($a['month'] ?? ''), (string)($b['month'] ?? '')); });
    $trend = null;
    if(count($completed) >= 2) {
        $latest = $completed[count($completed) - 1]; $previous = $completed[count($completed) - 2];
        try { $adjacent = (new \DateTimeImmutable((string)$previous['month'] . '-01'))->modify('+1 month')->format('Y-m') === (string)$latest['month']; } catch(\Throwable $exception) { $adjacent = false; }
        if($adjacent && (int)($previous['net_minor'] ?? 0) > 0) $trend = ['delta' => ((int)($latest['net_minor'] ?? 0) - (int)$previous['net_minor']) / (int)$previous['net_minor'] * 100, 'latest' => $latest['label'] ?? $latest['month'], 'previous' => $previous['label'] ?? $previous['month']];
    }
    ?>
    <p class="ff-source"><?= $fh($ft('Izvor: FCC evidencija plaćanja i stanje računa.')) ?> <?= $fh($ft('Osvježeno:')) ?> <time><?= $fh($fd($f['generated_at'] ?? null)) ?></time> <?= $fh($ft('Vrijeme: Europe/Zagreb.')) ?></p>
    <?php if(!empty($f['warnings'])): ?>
    <aside class="ff-notice" aria-label="<?= $fh($ft('Podaci za provjeru')) ?>">
        <strong><?= $fh($ft('Podaci za provjeru')) ?></strong>
        <ol><?php foreach($f['warnings'] as $warning): ?><li><?= $fh($warning['message'] ?? $ft('Dio evidencije zahtijeva provjeru.')) ?><?php if(isset($warning['count'])): ?> <span><?= $fh($ft('Broj zapisa:')) ?> <?= $fn($warning['count']) ?>.</span><?php endif ?></li><?php endforeach ?></ol>
    </aside>
    <?php endif ?>

    <section class="ff-surface ff-current" aria-labelledby="ff-current-title">
        <div class="ff-section-heading"><div><h2 id="ff-current-title"><?= $fh($ft('Trenutačno stanje računa')) ?></h2><p><?= $fh($ft('Broj računa u cijelom sustavu. Odabir mjeseca i valute ne mijenja ove podatke.')) ?></p></div></div>
        <div class="ff-status-shelf">
            <?php foreach([['pro_access','S PRO pristupom','Pravo pristupa, ne dokaz uplate.'],['ongoing_subscriptions','Pretplate za nastavak','Pretplate predviđene za nastavak.'],['renewing_paid','Plaćene uz obnovu','Plaćeni pristup s predviđenom obnovom.'],['trial','Proba prema FCC evidenciji','Pretplata s probom bez naplate nakon početka probe.'],['payment_risk','Problem s naplatom','Računi za provjeru naplate.']] as [$key,$label,$note]): ?>
            <a class="ff-status-item <?= $key === 'payment_risk' && (int)($summary[$key] ?? 0) > 0 ? 'ff-status-risk' : '' ?>" href="<?= $fh($flink(['state'=>$key])) ?>#ff-accounts" <?= $state === $key ? 'aria-current="true"' : '' ?>><span><?= $fh($ft($label)) ?></span><strong><?= $fn($summary[$key] ?? 0) ?></strong><small><?= $fh($ft($note)) ?></small></a>
            <?php endforeach ?>
        </div>
        <div class="ff-status-secondary">
            <?php foreach([['nonrenewing','Bez iduće obnove'],['access_revoked','Pristup ukinut'],['manual_granted','PRO bez pretplate'],['unknown_subscription','Pretplata za provjeru']] as [$key,$label]): ?><a href="<?= $fh($flink(['state'=>$key])) ?>#ff-accounts" <?= $state === $key ? 'aria-current="true"' : '' ?>><span><?= $fh($ft($label)) ?></span><strong><?= $fn($summary[$key] ?? 0) ?></strong></a><?php endforeach ?>
        </div>
        <p class="ff-note"><?= $fh($ft('Kategorije se mogu preklapati. PRO pristup sam po sebi ne znači da je račun plaćen. PRO bez pretplate uključuje dodijeljene pristupe i moguću probu bez kartice. Izvor ih ne razlikuje pouzdano.')) ?></p>
    </section>

    <section class="ff-surface ff-revenue" aria-labelledby="ff-revenue-title">
        <div class="ff-section-heading"><div><h2 id="ff-revenue-title"><?= $fh($ft('Prihodi po mjesecima')) ?></h2><p><?= $fh($ft('Uspješno evidentirana plaćanja i povrati u odabranoj valuti.')) ?></p></div><a class="ff-text-link" href="<?= $fh($flink(['export'=>'monthly'])) ?>"><?= $fh($ft('Preuzmi mjesečni CSV')) ?></a></div>
        <form method="get" action="<?= $fh(url('partner/admin')) ?>" class="ff-period-filters">
            <input type="hidden" name="finance" value="1"><input type="hidden" name="state" value="<?= $fh($state) ?>"><input type="hidden" name="search" value="<?= $fh($search) ?>">
            <label for="ff-months"><?= $fh($ft('Razdoblje')) ?><select id="ff-months" name="months" data-is-not-custom-select><?php foreach([3,6,12,24,48] as $option): ?><option value="<?= $option ?>" <?= $months === $option ? 'selected' : '' ?>><?= $option ?> <?= $fh($ft('mjeseci')) ?></option><?php endforeach ?></select></label>
            <label for="ff-currency"><?= $fh($ft('Valuta')) ?><select id="ff-currency" name="currency" data-is-not-custom-select><?php foreach($f['currencies'] ?? [$currency] as $option): $option = is_array($option) ? ($option['currency'] ?? '') : $option; ?><option value="<?= $fh($option) ?>" <?= strtoupper((string)$option) === $currency ? 'selected' : '' ?>><?= $fh(strtoupper((string)$option)) ?></option><?php endforeach ?></select></label>
            <label for="ff-month"><?= $fh($ft('Mjesec za detalje')) ?><select id="ff-month" name="selected_month" data-is-not-custom-select><option value="" <?= $selected_month === '' ? 'selected' : '' ?>><?= $fh($ft('Cijelo razdoblje, sažetak tekućeg mjeseca')) ?></option><?php foreach(array_reverse($monthly) as $row): ?><option value="<?= $fh($row['month'] ?? '') ?>" <?= ($row['month'] ?? '') === $selected_month ? 'selected' : '' ?>><?= $fh($row['label'] ?? $row['month'] ?? '') ?></option><?php endforeach ?></select></label>
            <button class="ff-button" type="submit"><?= $fh($ft('Primijeni odabir')) ?></button>
        </form>
        <div class="ff-money-overview">
            <div class="ff-money-month"><h3><?= $fh($month_label) ?></h3><?php if($display_month === $current_month): ?><span class="ff-badge"><?= $fh($ft('Mjesec je u tijeku')) ?></span><?php endif ?><dl><div><dt><?= $fh($ft('Neto nakon povrata')) ?></dt><dd class="ff-money-primary"><?= $fh($fm($selected['net_minor'] ?? 0)) ?></dd></div><div><dt><?= $fh($ft('Bruto naplaćeno')) ?></dt><dd><?= $fh($fm($selected['gross_minor'] ?? 0)) ?></dd></div><div><dt><?= $fh($ft('Povrati')) ?></dt><dd><?= $fh($fm($selected['refunded_minor'] ?? 0)) ?></dd></div></dl><p><?= $fn($selected['paid_count'] ?? 0) ?> <?= $fh($ft('uspješnih plaćanja.')) ?> <?= $fn($selected['failed_count'] ?? 0) ?> <?= $fh($ft('neuspjelih pokušaja.')) ?></p></div>
            <div class="ff-money-period"><h3><?= $fh($ft('Cijelo odabrano razdoblje')) ?></h3><dl><div><dt><?= $fh($ft('Neto nakon povrata')) ?></dt><dd><?= $fh($fm($totals['net_minor'] ?? 0)) ?></dd></div><div><dt><?= $fh($ft('Bruto naplaćeno')) ?></dt><dd><?= $fh($fm($totals['gross_minor'] ?? 0)) ?></dd></div><div><dt><?= $fh($ft('Povrati')) ?></dt><dd><?= $fh($fm($totals['refunded_minor'] ?? 0)) ?></dd></div></dl><p><?= $fn($totals['paid_count'] ?? 0) ?> <?= $fh($ft('uspješnih plaćanja.')) ?> <?= $fn($totals['unique_payers'] ?? 0) ?> <?= $fh($ft('različitih platitelja.')) ?></p><?php if($trend): ?><p class="ff-trend"><strong><?= $trend['delta'] > 0 ? '+' : '' ?><?= $fh(number_format($trend['delta'],1,',','.')) ?> %</strong> <?= $fh($ft('promjena neta između dovršenih mjeseci:')) ?> <?= $fh($trend['previous']) ?> <?= $fh($ft('i')) ?> <?= $fh($trend['latest']) ?>.</p><?php else: ?><p class="ff-note"><?= $fh($ft('Za postotnu usporedbu potrebna su dva uzastopna dovršena mjeseca i pozitivan neto u prvom mjesecu.')) ?></p><?php endif ?></div>
        </div>
        <?php if($monthly):
        $chart_width = 960; $left = 86; $right = 22; $plot_top = 18; $plot_bottom = 186; $plot_width = $chart_width - $left - $right; $plot_height = $plot_bottom - $plot_top;
        $net_values = array_map(static function($row): int { return (int)($row['net_minor'] ?? 0); },$monthly);
        $max_net = max(1,max($net_values)); $min_net = min(0,min($net_values)); $net_range = $max_net - $min_net;
        $net_y = static function($value) use ($plot_top,$plot_height,$max_net,$net_range): float { return $plot_top + (($max_net - $value) / $net_range) * $plot_height; };
        $zero_y = $net_y(0); $step = $plot_width / count($monthly); $bar_width = min(44,$step * .56); $label_stride = max(1,(int)ceil(count($monthly) / 12));
        ?>
        <figure class="ff-chart"><figcaption><?= $fh($ft('Neto prihodi')) ?> <span><?= $fh($currency) ?></span></figcaption>
            <div class="ff-chart-viewport" tabindex="0" aria-label="<?= $fh($ft('Graf po mjesecima')) ?>"><svg class="ff-chart-svg" viewBox="0 0 960 230" role="img" aria-labelledby="ff-net-title ff-net-desc"><title id="ff-net-title"><?= $fh($ft('Neto prihodi po mjesecima')) ?></title><desc id="ff-net-desc"><?= $fh($ft('Visina stupca prikazuje neto naplaćeni iznos nakon evidentiranih povrata. Stupci ispod nule predstavljaju negativan neto. Točne vrijednosti nalaze se u mjesečnoj tablici ispod grafova.')) ?></desc>
                <?php foreach([0,.5,1] as $fraction): $value = $max_net - ($net_range * $fraction); $y = $net_y($value); ?><line class="ff-gridline" x1="<?= $left ?>" y1="<?= $y ?>" x2="938" y2="<?= $y ?>"/><text class="ff-axis" x="74" y="<?= $y+4 ?>" text-anchor="end"><?= $fh(number_format($value / (10 ** $digits),$digits,',','.')) ?></text><?php endforeach ?>
                <line class="ff-baseline" x1="<?= $left ?>" y1="<?= $zero_y ?>" x2="938" y2="<?= $zero_y ?>"/>
                <?php foreach($monthly as $index => $row): $value=(int)($row['net_minor']??0); $x=$left+$step*$index+($step-$bar_width)/2; $value_y=$net_y($value); $height=abs($zero_y-$value_y); ?><rect class="<?= $value < 0 ? 'ff-bar-negative' : 'ff-bar-net' ?>" x="<?= $x ?>" y="<?= min($zero_y,$value_y) ?>" width="<?= $bar_width ?>" height="<?= $height ?>" rx="3"><title><?= $fh(($row['label']??$row['month']??'').': '.$fm($value)) ?></title></rect><?php if($index % $label_stride === 0 || $index === count($monthly)-1): ?><text class="ff-axis" x="<?= $left+$step*($index+.5) ?>" y="210" text-anchor="middle"><?= $fh(substr((string)($row['month']??''),5,2).'/'.substr((string)($row['month']??''),2,2)) ?></text><?php endif ?><?php endforeach ?>
            </svg></div><p class="ff-chart-scroll-note"><?= $fh($ft('Pomiči graf vodoravno za sve mjesece. Točne vrijednosti nalaze se u tablici ispod.')) ?></p>
        </figure>
        <?php $event_max=1;foreach($monthly as $row)$event_max=max($event_max,(int)($row['failed_count']??0),(int)($row['affected_users']??0),(int)($row['recovered_count']??0));$event_bar=min(16,$step*.22); ?>
        <?php if(!empty($f['first_billing_event_at'])): ?><p class="ff-note"><?= $fh($ft('Detaljni zapisi događaja naplate dostupni su od')) ?> <?= $fh($fd($f['first_billing_event_at'])) ?>. <?= $fh($ft('Raniji mjeseci nemaju evidenciju problema naplate.')) ?></p><?php endif ?>
        <figure class="ff-chart"><figcaption><?= $fh($ft('Problemi i oporavak naplate')) ?></figcaption><div class="ff-legend"><span><i class="ff-key-failed"></i><?= $fh($ft('Neuspjeli pokušaji')) ?></span><span><i class="ff-key-affected"></i><?= $fh($ft('Zahvaćeni računi')) ?></span><span><i class="ff-key-recovered"></i><?= $fh($ft('Oporavljeni računi')) ?></span></div>
            <div class="ff-chart-viewport" tabindex="0" aria-label="<?= $fh($ft('Graf po mjesecima')) ?>"><svg class="ff-chart-svg" viewBox="0 0 960 230" role="img" aria-labelledby="ff-risk-title ff-risk-desc"><title id="ff-risk-title"><?= $fh($ft('Neuspjeli pokušaji, zahvaćeni računi i oporavljeni računi po mjesecima')) ?></title><desc id="ff-risk-desc"><?= $fh($ft('Tri stupca za svaki mjesec. Jedan račun može imati više neuspjelih pokušaja. Oporavak označava uspješno plaćanje nakon evidentiranog problema. Točne vrijednosti nalaze se u mjesečnoj tablici.')) ?></desc>
                <?php foreach([0,.5,1] as $fraction): $value=(int)round($event_max*(1-$fraction));$y=$plot_bottom-$plot_height*$value/$event_max; ?><line class="ff-gridline" x1="<?= $left ?>" y1="<?= $y ?>" x2="938" y2="<?= $y ?>"/><text class="ff-axis" x="74" y="<?= $y+4 ?>" text-anchor="end"><?= $value ?></text><?php endforeach ?>
                <?php foreach($monthly as $index=>$row):foreach(['failed_count'=>'failed','affected_users'=>'affected','recovered_count'=>'recovered'] as $key=>$class):$series_index=array_search($key,['failed_count','affected_users','recovered_count'],true);$value=(int)($row[$key]??0);$height=$plot_height*$value/$event_max;$x=$left+$step*($index+.5)+($series_index-1.5)*$event_bar; ?><rect class="ff-bar-<?= $class ?>" x="<?= $x ?>" y="<?= $plot_bottom-$height ?>" width="<?= max(1,$event_bar-2) ?>" height="<?= $height ?>" rx="2"><title><?= $fh(($row['label']??$row['month']??'').': '.$fn($value)) ?></title></rect><?php endforeach;if($index%$label_stride===0||$index===count($monthly)-1): ?><text class="ff-axis" x="<?= $left+$step*($index+.5) ?>" y="210" text-anchor="middle"><?= $fh(substr((string)($row['month']??''),5,2).'/'.substr((string)($row['month']??''),2,2)) ?></text><?php endif;endforeach ?>
            </svg></div><p class="ff-chart-scroll-note"><?= $fh($ft('Pomiči graf vodoravno za sve mjesece. Točne vrijednosti nalaze se u tablici ispod.')) ?></p>
        </figure>
        <?php endif ?>
        <details class="ff-monthly-details" open><summary><?= $fh($ft('Mjesečni iznosi i naplata')) ?></summary>
            <div class="ff-table-scroll" tabindex="0" aria-label="<?= $fh($ft('Mjesečna financijska tablica')) ?>"><table class="ff-table ff-monthly-table"><caption class="ff-sr-only"><?= $fh($ft('Točni iznosi i događaji za svaki mjesec u odabranoj valuti.')) ?></caption><thead><tr><th scope="col"><?= $fh($ft('Mjesec')) ?></th><th scope="col"><?= $fh($ft('Bruto')) ?></th><th scope="col"><?= $fh($ft('Povrati')) ?></th><th scope="col"><?= $fh($ft('Neto')) ?></th><th scope="col"><?= $fh($ft('Plaćanja')) ?></th><th scope="col"><?= $fh($ft('Neuspjeli pokušaji')) ?></th><th scope="col"><?= $fh($ft('Računi s problemom')) ?></th><th scope="col"><?= $fh($ft('Neuspjeli računi za naplatu')) ?></th><th scope="col"><?= $fh($ft('Oporavljeni računi')) ?></th></tr></thead><tbody><?php foreach($monthly as $row): ?><tr <?= ($row['month']??'') === $selected_month ? 'class="ff-selected-row"' : '' ?>><th scope="row"><a href="<?= $fh($flink(['selected_month'=>$row['month']??''])) ?>"><?= $fh($row['label']??$row['month']??'') ?></a><?php if(($row['month']??'')===$current_month): ?><small><?= $fh($ft('U tijeku')) ?></small><?php endif ?></th><td><?= $fh($fm($row['gross_minor']??0)) ?></td><td><?= $fh($fm($row['refunded_minor']??0)) ?></td><td><strong><?= $fh($fm($row['net_minor']??0)) ?></strong></td><td><?= $fn($row['paid_count']??0) ?></td><td><?= !isset($row['events_recorded']) || $row['events_recorded'] ? $fn($row['failed_count']??0) : $fh($ft('Nije praćeno')) ?></td><td><?= !isset($row['events_recorded']) || $row['events_recorded'] ? $fn($row['affected_users']??0) : $fh($ft('Nije praćeno')) ?></td><td><?= !isset($row['events_recorded']) || $row['events_recorded'] ? $fn($row['failed_invoices']??0) : $fh($ft('Nije praćeno')) ?></td><td><?= !isset($row['events_recorded']) || $row['events_recorded'] ? $fn($row['recovered_count']??0) : $fh($ft('Nije praćeno')) ?></td></tr><?php endforeach ?></tbody><tfoot><tr><th scope="row"><?= $fh($ft('Ukupno razdoblje')) ?></th><td><?= $fh($fm($totals['gross_minor']??0)) ?></td><td><?= $fh($fm($totals['refunded_minor']??0)) ?></td><td><?= $fh($fm($totals['net_minor']??0)) ?></td><td><?= $fn($totals['paid_count']??0) ?></td><td><?= $fn($totals['failed_count']??0) ?></td><td colspan="3"><?= $fh($ft('Mjesečne račune ne zbrajamo.')) ?></td></tr></tfoot></table></div>
            <?php if(!$monthly): ?><p class="ff-empty-inline"><?= $fh($ft('Za odabrano razdoblje nema dostupne mjesečne evidencije.')) ?></p><?php endif ?>
        </details>
        <p class="ff-note"><?= $fh($ft('Neto ovdje znači bruto plaćanja umanjena za povrate. Naknade pružatelja naplate, porez i bankovna poravnanja nisu uključeni. Povrati se prikazuju prema datumu evidentiranog povrata. Valute se ne zbrajaju.')) ?></p>
    </section>

    <div class="ff-operations-grid">
        <section class="ff-surface" aria-labelledby="ff-risk-age-title"><div class="ff-section-heading"><div><h2 id="ff-risk-age-title"><?= $fh($ft('Otvoreni problemi s naplatom')) ?></h2><p><?= $fh($ft('Trenutačni rizik prema starosti zadnjeg evidentiranog problema.')) ?></p></div></div>
            <dl class="ff-age-list"><?php foreach([['days_0_7','Do 7 dana'],['days_8_30','Od 8 do 30 dana'],['days_31_plus','Više od 30 dana'],['unknown','Datum nije poznat']] as [$key,$label]): ?><div><dt><?= $fh($ft($label)) ?></dt><dd><?= $fn($risk_age[$key]??0) ?></dd></div><?php endforeach ?></dl>
            <h3 class="ff-subheading"><?= $fh($ft('Najčešći zabilježeni razlozi')) ?></h3>
            <?php if(!empty($f['top_reasons'])): ?><ol class="ff-reasons"><?php foreach($f['top_reasons'] as $reason): ?><li><span><?= $fh($ft($reason_label($reason['code']??''))) ?></span><strong><?= $fn($reason['count']??0) ?></strong></li><?php endforeach ?></ol><?php else: ?><p class="ff-empty-inline"><?= $fh($ft('Nema zabilježenih razloga za trenutačne probleme.')) ?></p><?php endif ?>
            <div class="ff-actions"><a class="ff-button" href="<?= $fh($flink(['state'=>'payment_risk'])) ?>#ff-accounts"><?= $fh($ft('Pregledaj račune s problemom')) ?></a><a class="ff-text-link" href="<?= $fh(url('admin/billing-risk')) ?>"><?= $fh($ft('Otvori radnje naplate')) ?></a></div>
        </section>
        <section class="ff-surface" aria-labelledby="ff-due-title"><div class="ff-section-heading"><div><h2 id="ff-due-title"><?= $fh($ft('Nadolazeća dospijeća')) ?></h2><p><?= $fh($ft('Prema trenutačno evidentiranom datumu idućeg plaćanja.')) ?></p></div></div>
            <dl class="ff-due-list"><div><dt><?= $fh($ft('U idućih 7 dana')) ?></dt><dd><strong><?= $fn($due['days_7_count']??0) ?> <?= $fh($ft('računa')) ?></strong><span><?= $fh($fm($due['days_7_minor']??0)) ?></span></dd></div><div><dt><?= $fh($ft('U idućih 30 dana')) ?></dt><dd><strong><?= $fn($due['days_30_count']??0) ?> <?= $fh($ft('računa')) ?></strong><span><?= $fh($fm($due['days_30_minor']??0)) ?></span></dd></div></dl>
            <p class="ff-note"><?= $fh($ft('Iznosi su procjena u odabranoj valuti, prema posljednjem poznatom iznosu računa. Uspješna naplata nije zajamčena. Pregled za 30 dana uključuje idućih 7 dana.')) ?></p><p class="ff-note"><?= $fh($ft('Procjena iznosa postoji za')) ?> <?= $fn($due['estimated_count']??0) ?> <?= $fh($ft('računa. Bez poznatog iznosa:')) ?> <?= $fn($due['unknown_amount_count']??0) ?>.</p>
            <a class="ff-text-link" href="<?= $fh($flink(['state'=>'ongoing_subscriptions'])) ?>#ff-accounts"><?= $fh($ft('Pregledaj pretplate za nastavak')) ?></a>
        </section>
    </div>

    <section class="ff-surface ff-accounts" id="ff-accounts" aria-labelledby="ff-accounts-title"><div class="ff-section-heading"><div><h2 id="ff-accounts-title"><?= $fh($ft('Računi i stanje naplate')) ?></h2><p><?= $fh($ft('Trenutačno stanje pojedinačnog računa. Odabir mjeseca sužava popis na račune s uplatom, povratom ili događajem naplate u tom mjesecu.')) ?></p></div><a class="ff-text-link" href="<?= $fh($flink(['export'=>'accounts'])) ?>"><?= $fh($ft('Preuzmi račune u CSV')) ?></a></div>
        <form method="get" action="<?= $fh(url('partner/admin')) ?>#ff-accounts" class="ff-account-filters"><input type="hidden" name="finance" value="1"><input type="hidden" name="months" value="<?= $months ?>"><input type="hidden" name="currency" value="<?= $fh($currency) ?>"><input type="hidden" name="selected_month" value="<?= $fh($selected_month) ?>"><label for="ff-search"><?= $fh($ft('Pretraži račune')) ?><input id="ff-search" name="search" type="search" maxlength="120" value="<?= $fh($search) ?>" placeholder="<?= $fh($ft('Ime ili adresa e pošte')) ?>"></label><label for="ff-state"><?= $fh($ft('Stanje računa')) ?><select id="ff-state" name="state" data-is-not-custom-select><?php foreach($states as $key=>$label): ?><option value="<?= $fh($key) ?>" <?= $state===$key?'selected':'' ?>><?= $fh($ft($label)) ?></option><?php endforeach ?></select></label><button class="ff-button" type="submit"><?= $fh($ft('Prikaži račune')) ?></button><?php if($state!==''||$search!==''): ?><a class="ff-text-link" href="<?= $fh($flink(['state'=>'','search'=>''])) ?>#ff-accounts"><?= $fh($ft('Očisti filtre')) ?></a><?php endif ?></form>
        <p class="ff-results"><?= $fh($ft('Pronađeno računa:')) ?> <strong><?= $fn($pagination['total']??count($accounts)) ?></strong><?php if($state!==''): ?>. <?= $fh($ft('Stanje:')) ?> <?= $fh($ft($states[$state]??'Svi računi')) ?><?php endif ?>.</p>
        <?php if(!$accounts): ?><div class="ff-empty-inline"><h3><?= $fh($ft('Nema računa za ovaj odabir.')) ?></h3><p><?= $fh($ft('Promijeni stanje računa ili pretragu za širi pregled.')) ?></p></div><?php else: ?>
        <div class="ff-account-list">
            <?php foreach($accounts as $account): $account_state=(string)($account['state']??'inactive');$account_currency=strtoupper((string)($account['currency']??$currency)); ?>
            <article class="ff-account-row <?= $account_state==='payment_risk'?'ff-account-risk':'' ?>">
                <div class="ff-account-identity"><a href="<?= $fh(url('admin/user-view/'.(int)($account['user_id']??0))) ?>"><?= $fh($account['name']??$ft('Korisnik')) ?></a><span><?= $fh($account['email']??'') ?></span><small><?= $fh($ft('Račun')) ?> <?= (int)($account['user_id']??0) ?>. <?= !empty($account['pro_access'])?$fh($ft('PRO pristup aktivan.')):$fh($ft('Bez PRO pristupa.')) ?></small></div>
                <div class="ff-account-state"><span class="ff-badge <?= $account_state==='payment_risk'?'ff-badge-risk':'' ?>"><?= $fh($ft($states[$account_state]??'Stanje za provjeru')) ?></span><small><?= $fh($ft('Naplata:')) ?> <?= $fh($account['processor']??$ft('Nije zabilježeno')) ?><?php if(!empty($account['stripe_status'])): ?>. <?= $fh($ft('Status pružatelja:')) ?> <?= $fh($ft($processor_statuses[$account['stripe_status']]??'Status za provjeru')) ?><?php endif ?></small><?php if(!empty($account['cancel_at_period_end'])): ?><small><?= $fh($ft('Obnova je isključena na kraju razdoblja.')) ?></small><?php endif ?></div>
                <dl class="ff-account-dates"><div><dt><?= $fh($ft(!empty($account['renewing'])?'Iduće dospijeće':'Pristup vrijedi do')) ?></dt><dd><?= $fh($fd($account['due_at']??null)) ?><?php if(!empty($account['renewing']) && isset($account['estimate_minor']) && $account_currency === $currency): ?><small><?= $fh($ft('Procjena:')) ?> <?= $fh($fm($account['estimate_minor'])) ?></small><?php elseif($account_currency !== ''): ?><small><?= $fh($ft('Valuta naplate:')) ?> <?= $fh($account_currency) ?></small><?php endif ?></dd></div><div><dt><?= $fh($ft('Posljednje plaćanje')) ?></dt><dd><?= $fh($fd($account['last_paid_at']??null)) ?></dd></div></dl>
                <?php if($account_state==='payment_risk'||!empty($account['failed_at'])): ?><div class="ff-account-problem"><strong><?= $fh($ft($reason_label($account['reason_code']??''))) ?></strong><?php if($account_state==='payment_risk'&&isset($account['risk_minor'])&&($account['risk_currency']??'')===$currency): ?><span><?= $fh($ft('Posljednji neuspjeli račun:')) ?> <?= $fh($fm($account['risk_minor'])) ?></span><?php endif ?><span><?= $fh($ft('Zadnji problem:')) ?> <?= $fh($fd($account['failed_at']??null)) ?></span><?php if(!empty($account['next_retry_at'])): ?><span><?= $fh($ft('Idući pokušaj:')) ?> <?= $fh($fd($account['next_retry_at'])) ?></span><?php endif ?><?php if(!empty($account['grace_until'])): ?><span><?= $fh($ft('Pristup u razdoblju odgode do:')) ?> <?= $fh($fd($account['grace_until'])) ?></span><?php endif ?></div><?php endif ?>
            </article>
            <?php endforeach ?>
        </div>
        <?php endif ?>
        <?php $page=max(1,(int)($pagination['page']??1));$pages=max(1,(int)($pagination['pages']??1));if($pages>1): ?><nav class="ff-pagination" aria-label="<?= $fh($ft('Stranice računa')) ?>"><?php if($page>1): ?><a class="ff-button ff-button-secondary" href="<?= $fh($flink(['page'=>$page-1])) ?>#ff-accounts"><?= $fh($ft('Prethodna stranica')) ?></a><?php endif ?><span><?= $fh($ft('Stranica')) ?> <?= $page ?> <?= $fh($ft('od')) ?> <?= $pages ?></span><?php if($page<$pages): ?><a class="ff-button ff-button-secondary" href="<?= $fh($flink(['page'=>$page+1])) ?>#ff-accounts"><?= $fh($ft('Sljedeća stranica')) ?></a><?php endif ?></nav><?php endif ?>
    </section>
    <details class="ff-surface ff-method"><summary><?= $fh($ft('Kako čitati ovaj pregled')) ?></summary><ol><li><?= $fh($ft('Plaćanja dolaze iz FCC evidencije uplata, a pokušaji i oporavci iz zapisa događaja naplate. Ako događaj nije zaprimljen ili je stariji status nepotpun, taj podatak zahtijeva provjeru kod pružatelja naplate.')) ?></li><li><?= $fh($ft('Neuspjeli pokušaji nisu broj neplaćenih računa. Isti račun može imati više pokušaja i kasnije uspješno platiti.')) ?></li><li><?= $fh($ft('PRO pristup, proba prema FCC evidenciji, pretplata i plaćanje različita su stanja. Pristup bez pretplate nije dokaz prihoda. U tom izvoru nije moguće pouzdano razdvojiti ručno dodijeljen pristup i probu bez kartice.')) ?></li><li><?= $fh($ft('Mjesečni iznosi prikazuju evidentirana plaćanja i povrate. Nisu izračun dobiti, raspoloživog bankovnog salda ni prihoda nakon poreza i svih troškova.')) ?></li><li><?= $fh($ft('Tekući mjesec je nepotpun. Postotna tendencija uspoređuje dva dovršena uzastopna mjeseca kada za usporedbu postoji valjana osnova.')) ?></li></ol></details>
    <?php endif ?>
</div>
<?php /* /Custom code: FC-2026-10-14 */ ?>
