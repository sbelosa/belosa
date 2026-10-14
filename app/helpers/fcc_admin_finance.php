<?php
/* Custom code: FC-2026-10-14: read only finance evidence and exact money aggregation. */
defined('ALTUMCODE') || die();

function fcc_admin_finance_json($value): array {
    if(is_string($value)) $value = json_decode($value, true);
    return is_array($value) ? $value : (is_object($value) ? (array) $value : []);
}

function fcc_admin_finance_minor_digits(string $currency): int {
    if(in_array($currency, ['BIF','CLP','DJF','GNF','ISK','JPY','KMF','KRW','PYG','RWF','UGX','UYI','VND','VUV','XAF','XOF','XPF'], true)) return 0;
    if(in_array($currency, ['BHD','IQD','JOD','KWD','LYD','OMR','TND'], true)) return 3;
    if(in_array($currency, ['CLF','UYW'], true)) return 4;
    return 2;
}

/** Native amounts are decimal major units, never provider API integer amounts. */
function fcc_admin_finance_money($value, string $currency): ?int {
    if(is_float($value)) {
        if(!is_finite($value)) return null;
        $value = sprintf('%.8F', $value);
    }
    if(!is_string($value) && !is_int($value)) return null;
    $value = trim((string) $value);
    if(!preg_match('/^([+-]?)(\d+)(?:\.(\d+))?$/D', $value, $match)) return null;
    $digits = fcc_admin_finance_minor_digits($currency);
    $fraction = $match[3] ?? '';
    $integer = ltrim($match[2] . substr(str_pad($fraction, $digits, '0'), 0, $digits), '0');
    $integer = $integer === '' ? '0' : $integer;
    $max = (string) PHP_INT_MAX;
    if(strlen($integer) > strlen($max) || (strlen($integer) === strlen($max) && strcmp($integer, $max) >= 0)) return null;
    $minor = (int) $integer;
    if((int) ($fraction[$digits] ?? '0') >= 5) $minor++;
    return ($match[1] ?? '') === '-' ? -$minor : $minor;
}

/** Persisted application datetimes are UTC. Calendar reporting uses Zagreb. */
function fcc_admin_finance_timestamp($value): ?int {
    if(!is_scalar($value) || trim((string) $value) === '') return null;
    try {
        $date = new DateTimeImmutable((string) $value, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();
        if($errors !== false && ($errors['warning_count'] || $errors['error_count'])) return null;
        return $date->getTimestamp();
    } catch(Throwable $exception) { return null; }
}

function fcc_admin_finance_filters(array $filters = []): array {
    $months = (int) ($filters['months'] ?? 12);
    $currency = strtoupper(trim(is_scalar($filters['currency'] ?? null) ? (string) $filters['currency'] : 'EUR'));
    $states = ['', 'pro_access','ongoing_subscriptions','renewing_paid','trial','payment_risk','nonrenewing','access_revoked','manual_granted','unknown_subscription','inactive'];
    $state = is_string($filters['state'] ?? null) ? $filters['state'] : '';
    $selected_month = is_string($filters['selected_month'] ?? null) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $filters['selected_month']) ? $filters['selected_month'] : '';
    $search = is_scalar($filters['search'] ?? null) ? trim((string) $filters['search']) : '';
    return [
        'months' => in_array($months, [3,6,12,24,48], true) ? $months : 12,
        'currency' => preg_match('/^[A-Z]{3}$/D', $currency) ? $currency : 'EUR',
        'selected_month' => $selected_month,
        'state' => in_array($state, $states, true) ? $state : '',
        'search' => function_exists('mb_substr') ? mb_substr($search, 0, 128) : substr($search, 0, 128),
        'page' => max(1, min(1000000, (int) ($filters['page'] ?? 1))),
        'per_page' => in_array((int) ($filters['per_page'] ?? 25), [10,25,50,100], true) ? (int) ($filters['per_page'] ?? 25) : 25,
    ];
}

function fcc_admin_finance_reduce(array $users, array $payments, array $events, array $filters = [], ?int $now = null): array {
    $now = $now ?? time();
    $filters = fcc_admin_finance_filters($filters);
    $currency = $filters['currency'];
    $timezone = new DateTimeZone('Europe/Zagreb');
    $current_month = (new DateTimeImmutable('@' . $now))->setTimezone($timezone)->modify('first day of this month')->setTime(0,0);
    $start = $current_month->modify('-' . ($filters['months'] - 1) . ' months');
    $end = $current_month->modify('+1 month');
    $monthly = [];
    for($date = $start; $date < $end; $date = $date->modify('+1 month')) {
        $month = $date->format('Y-m');
        $monthly[$month] = ['month'=>$month,'label'=>$date->format('m.Y.'),'gross_minor'=>0,'refunded_minor'=>0,'net_minor'=>0,'paid_count'=>0,'failed_count'=>0,'failed_invoices'=>0,'affected_users'=>0,'recovered_count'=>0,'unique_payers'=>0];
    }
    if(!isset($monthly[$filters['selected_month']])) $filters['selected_month'] = '';
    $warnings = [];
    $warn = static function(string $code, string $message) use (&$warnings): void {
        if(!isset($warnings[$code])) $warnings[$code] = ['code'=>$code,'message'=>$message,'count'=>0];
        $warnings[$code]['count']++;
    };
    $json_valid = static function($value): bool {
        if(!is_string($value) || trim($value)==='') return true;
        json_decode($value);
        return json_last_error()===JSON_ERROR_NONE;
    };
    $month_of = static function(?int $timestamp) use ($timezone, $start, $end, $now): ?string {
        if($timestamp === null || $timestamp < $start->getTimestamp() || $timestamp >= $end->getTimestamp() || $timestamp > $now) return null;
        return (new DateTimeImmutable('@'.$timestamp))->setTimezone($timezone)->format('Y-m');
    };
    $currencies = ['EUR'=>true, $currency=>true];
    $latest_paid = [];
    $latest_recurring = [];
    $latest_matching_paid = [];
    $paid_users = [];
    $payers_by_month = [];
    $activities_by_month = [];
    $first_payment_at = null;
    $payment_seen = [];
    foreach($payments as $row) {
        $row = (array) $row;
        $status = (string) ($row['status'] ?? '');
        if(!in_array($status, ['paid','refunded'], true)) continue;
        $native_currency = strtoupper(trim((string) ($row['currency'] ?? '')));
        if(!preg_match('/^[A-Z]{3}$/D', $native_currency)) { $warn('invalid_currency','Neke uplate nemaju valjanu valutu i nisu uključene u novčane iznose.'); continue; }
        $currencies[$native_currency] = true;
        $payment_key = !empty($row['payment_id']) ? 'provider:' . ($row['processor'] ?? '') . ':' . $row['payment_id'] : (!empty($row['id']) ? 'id:' . $row['id'] : null);
        if($payment_key !== null && isset($payment_seen[$payment_key])) { $warn('duplicate_payment','Duplikati iste evidentirane uplate izostavljeni su iz zbroja.'); continue; }
        $amount = fcc_admin_finance_money($row['total_amount'] ?? null, $native_currency);
        $timestamp = fcc_admin_finance_timestamp($row['datetime'] ?? null);
        if($amount === null || $amount < 0 || $timestamp === null || $timestamp > $now) { $warn('invalid_payment','Uplate s neispravnim iznosom ili datumom nisu uključene u izračun.'); continue; }
        if($payment_key !== null) $payment_seen[$payment_key] = true;
        $uid = (int) ($row['user_id'] ?? 0);
        $row['_timestamp'] = $timestamp;
        $row['_minor'] = $amount;
        $row['currency'] = $native_currency;
        if($amount > 0) {
            $first_payment_at = $first_payment_at === null ? $timestamp : min($first_payment_at, $timestamp);
            if($uid > 0) $paid_users[$uid] = true;
            else $warn('unidentified_payment','Dio uplata pripada obrisanim ili nepovezanim računima. Uključene su u novčani promet, ali nisu ubrojene među poznate platitelje.');
            if(!isset($latest_paid[$uid]) || $timestamp > $latest_paid[$uid]['_timestamp'] || ($timestamp === $latest_paid[$uid]['_timestamp'] && (int)($row['id']??0) > (int)($latest_paid[$uid]['id']??0))) $latest_paid[$uid] = $row;
            $recurring_key = $uid . ':' . (string)($row['processor']??'') . ':' . (string)($row['plan_id']??'');
            if(!isset($latest_matching_paid[$recurring_key]) || $timestamp > $latest_matching_paid[$recurring_key]['_timestamp'] || ($timestamp === $latest_matching_paid[$recurring_key]['_timestamp'] && (int)($row['id']??0) > (int)($latest_matching_paid[$recurring_key]['id']??0))) $latest_matching_paid[$recurring_key] = $row;
            if(($row['type']??'') === 'recurring' && (!isset($latest_recurring[$recurring_key]) || ($timestamp > $latest_recurring[$recurring_key]['_timestamp'] || ($timestamp === $latest_recurring[$recurring_key]['_timestamp'] && (int)($row['id']??0) > (int)($latest_recurring[$recurring_key]['id']??0))))) $latest_recurring[$recurring_key] = $row;
        }
        $month = $month_of($timestamp);
        if($native_currency === $currency && $month !== null) {
            $monthly[$month]['gross_minor'] += $amount;
            if($amount > 0) { $monthly[$month]['paid_count']++; if($uid>0) { $payers_by_month[$month][$uid] = true; $activities_by_month[$month][$uid] = true; } }
        }
        $refunded = fcc_admin_finance_money($row['refunded_total'] ?? '0', $native_currency);
        if($refunded === null || $refunded < 0) { $warn('invalid_refund','Neki povrati imaju neispravan ukupan iznos.'); $refunded = 0; }
        $refunded = min($amount, $refunded);
        if(!$json_valid($row['refunds']??[])) $warn('invalid_json','Neki zapisi imaju neispravan JSON, pa se njihove pojedinosti ne mogu potvrditi.');
        $recorded_refund = 0;
        $refund_seen = [];
        foreach(fcc_admin_finance_json($row['refunds']??[]) as $refund) {
            $refund = (array) $refund;
            $refund_key = (string) ($refund['stripe_refund_id'] ?? $refund['id'] ?? '');
            if($refund_key !== '' && isset($refund_seen[$refund_key])) continue;
            if($refund_key !== '') $refund_seen[$refund_key] = true;
            $refund_minor = fcc_admin_finance_money($refund['amount']??null, $native_currency);
            if($refund_minor === null || $refund_minor < 0) { $warn('invalid_refund','Neki povrati imaju neispravan ukupan iznos.'); continue; }
            $refund_minor = min($refund_minor, max(0, $refunded - $recorded_refund));
            $refund_timestamp = fcc_admin_finance_timestamp($refund['datetime']??null);
            if($refund_timestamp === null) { $refund_timestamp = $timestamp; $warn('refund_date_fallback','Povrat bez valjanog datuma pripisan je mjesecu izvorne uplate.'); }
            if($refund_timestamp > $now) { $warn('future_refund','Povrat s budućim datumom nije uključen u novčani promet.'); $recorded_refund += $refund_minor; continue; }
            $recorded_refund += $refund_minor;
            $refund_month = $month_of($refund_timestamp);
            if($native_currency === $currency && $refund_month !== null) { $monthly[$refund_month]['refunded_minor'] += $refund_minor; $activities_by_month[$refund_month][$uid] = true; }
        }
        if($refunded > $recorded_refund) {
            $warn('refund_date_fallback','Povrat bez valjanog datuma pripisan je mjesecu izvorne uplate.');
            if($native_currency === $currency && $month !== null) { $monthly[$month]['refunded_minor'] += $refunded - $recorded_refund; $activities_by_month[$month][$uid] = true; }
        }
    }
    $event_seen = [];
    $event_users = [];
    $failed_invoices = [];
    $latest_failure = [];
    $events_by_month = [];
    $first_billing_event_at = null;
    foreach($events as $row) {
        $row = (array) $row;
        if(!$json_valid($row['data']??[]) || (isset($row['data_json_valid']) && !(bool)$row['data_json_valid'])) $warn('invalid_json','Neki zapisi imaju neispravan JSON, pa se njihove pojedinosti ne mogu potvrditi.');
        $event = isset($row['data']) ? fcc_admin_finance_json($row['data']) : $row;
        $record_timestamp = fcc_admin_finance_timestamp($event['occurred_at']??$row['datetime']??null);
        if($record_timestamp !== null && $record_timestamp <= $now) $first_billing_event_at = $first_billing_event_at === null ? $record_timestamp : min($first_billing_event_at,$record_timestamp);
        $event_type = (string) ($event['event_type']??'');
        if(!in_array($event_type, ['payment_failed','payment_recovered','payment_confirmed','subscription_status_changed'], true)) continue;
        $event_id = trim((string) ($event['stripe_event_id'] ?? ''));
        $event_key = $event_id !== '' ? ($event['processor']??'stripe').':'.$event_id : (!empty($row['id']) ? 'row:'.$row['id'] : null);
        if($event_key !== null && isset($event_seen[$event_key])) continue;
        $timestamp = fcc_admin_finance_timestamp($event['occurred_at']??$row['datetime']??null);
        if($timestamp === null || $timestamp > $now) { $warn('invalid_event','Neki događaji naplate nemaju valjan datum i nisu uključeni u trend.'); continue; }
        if($event_key !== null) $event_seen[$event_key] = true;
        $uid = (int) ($row['user_id']??$event['user_id']??0);
        $event['user_id'] = $uid;
        $event['_timestamp'] = $timestamp;
        $event['_row_id'] = (int)($row['id']??0);
        $native_currency = strtoupper(trim((string)($event['currency']??'')));
        if(preg_match('/^[A-Z]{3}$/D', $native_currency)) $currencies[$native_currency] = true;
        if($event_type === 'payment_failed' && (!isset($latest_failure[$uid]) || ($timestamp > $latest_failure[$uid]['_timestamp'] || ($timestamp === $latest_failure[$uid]['_timestamp'] && $event['_row_id'] >= $latest_failure[$uid]['_row_id'])))) $latest_failure[$uid] = $event;
        $month = $month_of($timestamp);
        if($month === null || !in_array($event_type, ['payment_failed','payment_recovered'], true)) continue;
        /* Currencyless events stay visible in all currency views; their amounts are never added. */
        if($native_currency !== '' && $native_currency !== $currency) continue;
        $events_by_month[$month][$uid] = true;
        if($event_type === 'payment_failed') {
            $monthly[$month]['failed_count']++;
            $event_users[$month][$uid] = true;
            $invoice = trim((string) ($event['stripe_invoice_id']??''));
            $failed_invoices[$month][$invoice !== '' ? ($event['processor']??'stripe').':'.$invoice : 'unknown:'.$uid.':'.($event_key??$timestamp)] = true;
        } else $monthly[$month]['recovered_count']++;
    }
    $summary = array_fill_keys(['pro_access','renewing_paid','trial','payment_risk','nonrenewing','access_revoked','manual_granted','ongoing_subscriptions','unknown_subscription','inactive'], 0);
    $risk_age = ['days_0_7'=>0,'days_8_30'=>0,'days_31_plus'=>0,'unknown'=>0];
    $next_due = ['days_7_count'=>0,'days_30_count'=>0,'days_7_minor'=>0,'days_30_minor'=>0,'estimated_count'=>0,'unknown_amount_count'=>0];
    $reasons = [];
    $accounts = [];
    $lower = static fn(string $value): string => function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    $seen_users = [];
    foreach($users as $row) {
        $row = (array) $row;
        if((int)($row['type']??-1) !== 0) continue;
        $uid = (int)($row['user_id']??0);
        if(isset($seen_users[$uid])) continue;
        $seen_users[$uid] = true;
        if(!$json_valid($row['extra']??[]) || !$json_valid($row['plan_settings']??[]) || (isset($row['extra_json_valid']) && !(bool)$row['extra_json_valid']) || (isset($row['plan_json_valid']) && !(bool)$row['plan_json_valid'])) $warn('invalid_json','Neki zapisi imaju neispravan JSON, pa se njihove pojedinosti ne mogu potvrditi.');
        $extra = fcc_admin_finance_json($row['extra']??[]);
        $plan = fcc_admin_finance_json($row['plan_settings']??[]);
        $billing_state = (string)($extra['billing_state']??'healthy');
        $stripe_status = (string)($extra['billing_stripe_status']??'');
        $pro_capability = in_array($plan['ai_growth_plan_is_enabled']??false, [true,1,'1'], true);
        $expiration_text = trim((string)($row['plan_expiration_date']??''));
        $expiration = fcc_admin_finance_timestamp($expiration_text);
        $grace = fcc_admin_finance_timestamp($extra['billing_grace_until']??null);
        $revoked = !empty($extra['billing_access_revoked_at']) || $billing_state === 'access_revoked';
        $risk = in_array($billing_state, ['past_due','past_due_critical'], true);
        /* Mirrors fcc_pro_access_for_user, with explicit customer type and injected now. */
        $pro_access = (int)($row['status']??0) === 1 && $pro_capability && empty($extra['billing_access_revoked_at']) && ($expiration_text === '' || ($expiration !== null && $expiration >= $now) || ($risk && $grace !== null && $grace >= $now));
        $subscription = trim((string)($row['payment_subscription_id']??''));
        $terminal = in_array($stripe_status, ['canceled','unpaid','incomplete_expired','paused'], true);
        $cancel = in_array($extra['billing_cancel_at_period_end']??false, [true,1,'1'], true) || !empty($extra['billing_subscription_cancelled_at']);
        $ongoing = $subscription !== '' && !$terminal && !$cancel && !$revoked;
        $processor = trim((string)($row['payment_processor']??''));
        $recurring = $latest_recurring[$uid.':'.$processor.':'.(string)($row['plan_id']??'')]??null;
        $matching_paid = $latest_matching_paid[$uid.':'.$processor.':'.(string)($row['plan_id']??'')]??null;
        $trial_started = fcc_admin_finance_timestamp($extra['billing_trial_started_at']??null);
        $trial_days = max(0,(int)($row['plan_trial_days']??$row['trial_days']??0));
        $trial = $subscription !== '' && $pro_access && !$risk && !$revoked && $trial_started !== null && $trial_days > 0 && $trial_started <= $now && $expiration !== null && $expiration >= $now && $expiration <= $trial_started+($trial_days+1)*86400 && $now <= $trial_started+($trial_days+1)*86400 && ($matching_paid === null || $matching_paid['_timestamp'] < $trial_started-3600);
        $paid_evidence = $recurring !== null && $recurring['_minor'] > 0;
        $due = fcc_admin_finance_timestamp($extra['billing_current_period_end']??null) ?? $expiration;
        $evidence_days = match($recurring['frequency']??'') { 'monthly'=>40, 'quarterly'=>102, 'biannual'=>193, 'annual'=>373, default=>0 };
        $paid_current = $paid_evidence && $evidence_days > 0 && $recurring['_timestamp'] >= $now-$evidence_days*86400;
        $renewing_paid = $ongoing && !$trial && !$risk && $pro_access && $due !== null && $due >= $now && $paid_current && ($processor !== 'stripe' || $stripe_status === 'active');
        if($billing_state === 'access_revoked' && $pro_access) $warn('billing_state_conflict','Dio računa ima nesklad između oznake zaustavljenog pristupa i stvarnih prava pristupa. Potrebna je provjera korisničkog detalja.');
        $nonrenewing = !$revoked && !$risk && !$trial && ($cancel || $terminal) && ($pro_access || $subscription !== '');
        $manual = $pro_access && !$risk && !$trial && !$nonrenewing && $subscription === '';
        if($manual && (int)($row['plan_trial_done']??0)===1 && !isset($paid_users[$uid])) $warn('unverified_trial','Pristup bez automatske naplate može uključivati probu bez kartice. Postojeći podaci ne razlikuju pouzdano probu od dodijeljenog pristupa.');
        $unknown = !$revoked && !$risk && !$trial && !$nonrenewing && !$renewing_paid && $subscription !== '';
        $state = $revoked ? 'access_revoked' : ($risk ? 'payment_risk' : ($trial ? 'trial' : ($nonrenewing ? 'nonrenewing' : ($renewing_paid ? 'renewing_paid' : ($unknown ? 'unknown_subscription' : ($manual ? 'manual_granted' : 'inactive'))))));
        if(!$pro_capability && (string)($row['plan_id']??'') !== '5' && $subscription === '' && !$revoked && !$risk && !isset($paid_users[$uid])) continue;
        foreach(['pro_access'=>$pro_access,'ongoing_subscriptions'=>$ongoing,'renewing_paid'=>$renewing_paid,'trial'=>$trial,'payment_risk'=>$risk,'nonrenewing'=>$nonrenewing,'access_revoked'=>$revoked,'manual_granted'=>$manual,'unknown_subscription'=>$unknown,'inactive'=>$state==='inactive'] as $key=>$value) if($value) $summary[$key]++;
        $failure = $latest_failure[$uid]??[];
        $failed_at = fcc_admin_finance_timestamp($extra['billing_last_failed_at']??null) ?? ($failure['_timestamp']??null);
        $reason_code = trim((string)($extra['billing_last_failed_reason_code']??$failure['reason_code']??'')) ?: 'unknown';
        if($risk) {
            $risk_started = fcc_admin_finance_timestamp($extra['billing_grace_started_at']??null) ?? $failed_at;
            $age = $risk_started === null ? null : max(0, (int)floor(($now-$risk_started)/86400));
            $risk_age[$age===null?'unknown':($age<=7?'days_0_7':($age<=30?'days_8_30':'days_31_plus'))]++;
            $reasons[$reason_code] = ($reasons[$reason_code]??0) + 1;
        }
        $native_currency = $recurring['currency']??strtoupper(trim((string)($row['payment_currency']??'')));
        if(preg_match('/^[A-Z]{3}$/D', $native_currency)) $currencies[$native_currency] = true;
        $estimate = $paid_current && $pro_access && in_array($recurring['frequency']??'', ['monthly','quarterly','biannual','annual'], true) ? $recurring['_minor'] : null;
        $due_eligible = $ongoing && !$risk && $due !== null && $due >= $now;
        if($due_eligible && $due <= $now+30*86400) {
            $next_due['days_30_count']++;
            $in_seven = $due <= $now+7*86400;
            if($in_seven) $next_due['days_7_count']++;
            if($estimate !== null && $native_currency === $currency) {
                $next_due['days_30_minor'] += $estimate;
                if($in_seven) $next_due['days_7_minor'] += $estimate;
                $next_due['estimated_count']++;
            } elseif($estimate === null) $next_due['unknown_amount_count']++;
        }
        $account = [
            'user_id'=>$uid,'name'=>(string)($row['name']??''),'email'=>(string)($row['email']??''),'plan_id'=>(string)($row['plan_id']??''),'state'=>$state,
            'billing_state'=>$billing_state,'stripe_status'=>$stripe_status,'pro_access'=>$pro_access,'subscription'=>$subscription,'renewing'=>$ongoing,'trial'=>$trial,
            'cancel_at_period_end'=>$cancel,'trial_evidence'=>$trial?'fcc_marker_without_paid_charge':null,'trial_started_at'=>$trial_started===null?null:gmdate('Y-m-d H:i:s',$trial_started),'frequency'=>$recurring['frequency']??null,'processor'=>$processor,'currency'=>$native_currency,'due_at'=>$due===null?null:gmdate('Y-m-d H:i:s',$due),'estimate_minor'=>$estimate,
            'failed_at'=>$failed_at===null?null:gmdate('Y-m-d H:i:s',$failed_at),'reason_code'=>$reason_code,'reason_text'=>(string)($extra['billing_last_failed_reason_text']??$failure['reason_text']??''),
            'next_retry_at'=>$extra['billing_next_retry_at']??null,'grace_until'=>$extra['billing_grace_until']??null,'last_paid_at'=>isset($latest_paid[$uid])?gmdate('Y-m-d H:i:s',$latest_paid[$uid]['_timestamp']):null,
            'failed_attempts'=>(int)($extra['billing_failed_attempts']??0),
            'risk_minor'=>isset($failure['amount'])?fcc_admin_finance_money($failure['amount'],strtoupper((string)($failure['currency']??$native_currency))):null,
            'risk_currency'=>strtoupper((string)($failure['currency']??$native_currency)),
        ];
        if($native_currency !== '' && $native_currency !== $currency) continue;
        if($filters['state'] === 'pro_access' && !$pro_access) continue;
        if($filters['state'] === 'ongoing_subscriptions' && !$ongoing) continue;
        if($filters['state'] !== '' && !in_array($filters['state'], ['pro_access','ongoing_subscriptions'], true) && $state !== $filters['state']) continue;
        if($filters['search'] !== '' && strpos($lower($account['name'].' '.$account['email'].' '.$uid.' '.$account['reason_code'].' '.$account['reason_text']), $lower($filters['search'])) === false) continue;
        if($filters['selected_month'] !== '' && !isset($activities_by_month[$filters['selected_month']][$uid]) && !isset($events_by_month[$filters['selected_month']][$uid])) continue;
        $accounts[] = $account;
    }
    $totals = ['gross_minor'=>0,'refunded_minor'=>0,'net_minor'=>0,'paid_count'=>0,'failed_count'=>0,'failed_invoices'=>0,'affected_users'=>0,'recovered_count'=>0,'unique_payers'=>0];
    $total_payers = $total_affected = $total_invoices = [];
    foreach($monthly as $month=>&$values) {
        $month_end = (new DateTimeImmutable($month.'-01', $timezone))->modify('+1 month')->getTimestamp();
        $values['events_recorded'] = $first_billing_event_at !== null && $month_end > $first_billing_event_at;
        $values['net_minor'] = $values['gross_minor']-$values['refunded_minor'];
        $values['affected_users'] = count($event_users[$month]??[]);
        $values['failed_invoices'] = count($failed_invoices[$month]??[]);
        $values['unique_payers'] = count($payers_by_month[$month]??[]);
        foreach(array_keys($totals) as $key) $totals[$key] += $values[$key];
        $total_payers += $payers_by_month[$month]??[];
        $total_affected += $event_users[$month]??[];
        $total_invoices += $failed_invoices[$month]??[];
    }
    unset($values);
    $totals['unique_payers'] = count($total_payers);
    $totals['affected_users'] = count($total_affected);
    $totals['failed_invoices'] = count($total_invoices);
    arsort($reasons);
    $top_reasons = [];
    foreach(array_slice($reasons,0,8,true) as $code=>$count) $top_reasons[] = ['code'=>$code,'count'=>$count];
    $priority = ['payment_risk'=>0,'unknown_subscription'=>1,'access_revoked'=>2,'trial'=>3,'renewing_paid'=>4,'nonrenewing'=>5,'manual_granted'=>6,'inactive'=>7];
    usort($accounts, static function(array $left, array $right) use ($priority): int {
        return ($priority[$left['state']]<=>$priority[$right['state']]) ?: (($left['due_at']??'9999')<=>($right['due_at']??'9999')) ?: strcmp($left['name'],$right['name']) ?: ($left['user_id']<=>$right['user_id']);
    });
    $total_accounts = count($accounts);
    $pages = max(1,(int)ceil($total_accounts/$filters['per_page']));
    $filters['page'] = min($filters['page'],$pages);
    $export_accounts = $accounts;
    $accounts = array_slice($accounts,($filters['page']-1)*$filters['per_page'],$filters['per_page']);
    $currency_list = array_keys($currencies);
    sort($currency_list);
    if($summary['unknown_subscription'] > 0) $warnings['unknown_subscription'] = ['code'=>'unknown_subscription','message'=>'Dio pretplata nema dovoljno dokaza o aktualnom statusu ili uspješnoj obnovi.','count'=>$summary['unknown_subscription']];
    if($first_billing_event_at !== null && $start->getTimestamp() < $first_billing_event_at) $warnings['event_history_partial'] = ['code'=>'event_history_partial','message'=>'Događaji naplate nisu evidentirani za cijelo prikazano razdoblje. Nulte vrijednosti prije početka evidencije ne dokazuju da nije bilo problema.','count'=>1];
    return [
        'available'=>true,'error'=>null,'generated_at'=>gmdate('Y-m-d H:i:s',$now),'timezone'=>'Europe/Zagreb','filters'=>$filters,'currencies'=>$currency_list,'minor_digits'=>fcc_admin_finance_minor_digits($currency),
        'summary'=>$summary,'monthly'=>array_values($monthly),'totals'=>$totals,'selected_month_totals'=>$filters['selected_month']!==''?$monthly[$filters['selected_month']]:null,
        'risk_age'=>$risk_age,'next_due'=>$next_due,'top_reasons'=>$top_reasons,'accounts'=>$accounts,'export_accounts'=>$export_accounts,'pagination'=>['page'=>$filters['page'],'per_page'=>$filters['per_page'],'total'=>$total_accounts,'pages'=>$pages],
        'warnings'=>array_values($warnings),'first_payment_at'=>$first_payment_at===null?null:gmdate('Y-m-d H:i:s',$first_payment_at),
        'first_billing_event_at'=>$first_billing_event_at===null?null:gmdate('Y-m-d H:i:s',$first_billing_event_at),
        'month_start'=>gmdate('Y-m-d H:i:s',$start->getTimestamp()),'month_end'=>gmdate('Y-m-d H:i:s',$end->getTimestamp()),
        'forecast_note'=>'Iznosi obnove su procjena prema zadnjoj evidentiranoj uspješnoj ponavljajućoj uplati istog paketa i procesora. Probni računi bez uplate nemaju procijenjen iznos. Budući popusti, promjene cijene, poreza i naknade nisu predviđeni.',
        'revenue_note'=>'Novčani promet prikazuje evidentirane uplate i povrate po valuti i mjesecu u vremenskoj zoni Europe/Zagreb. Ne uključuje naknade procesora ni isplate na banku. Računi i problemi naplate prikazuju trenutačno stanje.',
    ];
}

function fcc_admin_finance_csv_cell($value): string {
    $text = is_scalar($value) || $value === null ? (string) $value : '';
    return preg_match('/^(?:[\t\r\n]|[\s\x00-\x1f\x{FEFF}]*(?:[=+@-]|[\t\r\n]))/u', $text) ? "'".$text : $text;
}

/** Only SELECT statements, no provider requests, notifications, recovery or writes. */
function fcc_admin_finance_dashboard(array $filters = []): array {
    try {
        if(!function_exists('fcc_partner_rows')) throw new RuntimeException('Finance storage unavailable');
        $billing_fields = ['billing_state','billing_stripe_status','billing_trial_started_at','billing_subscription_started_at','billing_subscription_cancelled_during_trial','billing_grace_until','billing_grace_started_at','billing_access_revoked_at','billing_last_failed_at','billing_last_failed_reason_code','billing_last_failed_reason_text','billing_next_retry_at','billing_current_period_end','billing_cancel_at_period_end','billing_subscription_cancelled_at','billing_failed_attempts'];
        $json_pairs = [];
        foreach($billing_fields as $field) $json_pairs[] = "'{$field}', JSON_EXTRACT(`extra`, '$.{$field}')";
        $users = fcc_partner_rows("SELECT user_id,type,status,name,email,plan_id,plan_expiration_date,payment_processor,payment_subscription_id,payment_currency,plan_trial_done,(SELECT MAX(trial_days) FROM plans WHERE CAST(plans.plan_id AS CHAR)=CAST(users.plan_id AS CHAR)) AS plan_trial_days,CASE WHEN extra IS NULL OR extra='' THEN 1 ELSE JSON_VALID(extra) END AS extra_json_valid,CASE WHEN plan_settings IS NULL OR plan_settings='' THEN 1 ELSE JSON_VALID(plan_settings) END AS plan_json_valid, CASE WHEN JSON_VALID(plan_settings) THEN JSON_OBJECT('ai_growth_plan_is_enabled',JSON_EXTRACT(plan_settings,'$.ai_growth_plan_is_enabled')) ELSE '{}' END AS plan_settings, CASE WHEN JSON_VALID(extra) THEN JSON_OBJECT(".implode(',',$json_pairs).") ELSE '{}' END AS extra FROM users WHERE type=0");
        $payments = fcc_partner_rows('SELECT id,user_id,plan_id,payment_id,processor,type,frequency,total_amount,currency,status,refunded_total,refunded_status,refunds,datetime FROM payments');
        $events = fcc_partner_rows("SELECT datum_id AS id,user_id,datetime,JSON_VALID(data) AS data_json_valid, CASE WHEN JSON_VALID(data) THEN JSON_OBJECT('event_type',JSON_EXTRACT(data,'$.event_type'),'occurred_at',JSON_EXTRACT(data,'$.occurred_at'),'processor',JSON_EXTRACT(data,'$.processor'),'stripe_event_id',JSON_EXTRACT(data,'$.stripe_event_id'),'stripe_invoice_id',JSON_EXTRACT(data,'$.stripe_invoice_id'),'stripe_subscription_id',JSON_EXTRACT(data,'$.stripe_subscription_id'),'amount',JSON_EXTRACT(data,'$.amount'),'currency',JSON_EXTRACT(data,'$.currency'),'reason_code',JSON_EXTRACT(data,'$.reason_code'),'reason_text',JSON_EXTRACT(data,'$.reason_text')) ELSE '{}' END AS data FROM data WHERE type='billing_event'");
        return fcc_admin_finance_reduce($users,$payments,$events,$filters);
    } catch(Throwable $exception) {
        error_log('FCC admin finance unavailable: '.get_class($exception));
        return ['available'=>false,'error'=>'Financijske podatke trenutačno nije moguće učitati. Ponovno učitaj stranicu nakon provjere dostupnosti baze.','generated_at'=>gmdate('Y-m-d H:i:s'),'timezone'=>'Europe/Zagreb','filters'=>fcc_admin_finance_filters($filters)];
    }
}
/* /Custom code: FC-2026-10-14 */
