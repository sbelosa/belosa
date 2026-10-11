<?php
/** Integration candidate. Pure functions: no database writes, enrolment or runtime activation. */
defined('ALTUMCODE') || die();

function fcc90_catalog(): array {
    static $catalog;
    if($catalog === null) {
        $catalog = json_decode(file_get_contents(__DIR__ . '/../config/curriculum/fcc90.hr.v1.json'), true, 512, JSON_THROW_ON_ERROR);
        if(($catalog['schema_version'] ?? 0) !== 1 || count($catalog['tasks'] ?? []) !== 90) {
            throw new RuntimeException(fcc_t('FCC90 catalog contract mismatch.'));
        }
    }
    return $catalog;
}

/** Maps content to the existing presentation vocabulary, NOT the legacy completion validator. */
function fcc90_task(int $position, int $level = 1, bool $practice = false): array {
    if($position < 1 || $position > 90 || $level < 1 || $level > 4) throw new InvalidArgumentException(fcc_t('Invalid FCC90 position or level.'));
    $catalog = fcc90_catalog();
    $task = $catalog['tasks'][$position - 1];
    $variant = $task['levels'][(string)$level];
    return [
        'key'=>$task['id'], 'sequence_position'=>$position, 'sequence_total'=>90,
        'content_version'=>$task['content_version'], 'catalog_version'=>$catalog['catalog_version'],
        'level'=>$level, 'core'=>$task['core'], 'title'=>$task['title'],
        'instruction'=>implode(' ', $task['steps']) . ' ' . $variant['adaptation'],
        'success_definition'=>$practice ? $task['help']['if_no_opportunity'] . ' Označi vježbu; bez poslovnog ishoda.' : $task['completion']['criterion'],
        'target'=>1, 'quick_target'=>1,
        'expected_result_type'=>$practice ? 'training' : $task['completion']['result_type_applied'],
        'allowed_result_types'=>array_values(array_unique([$task['completion']['result_type_applied'], 'training'])),
        'checklist'=>$task['completion']['self_check'], 'fallback'=>$task['help']['if_no_opportunity'],
        'message_example'=>$task['example'], 'communication'=>$task['communication'], 'lesson'=>$task['why'], 'help'=>$task['help'],
        'resources'=>array_map(static fn($id)=>$catalog['resources'][$id], $task['resources']),
        'minutes'=>$variant['minutes'], 'variant_note'=>$variant['completion_addendum'],
        'can_complete'=>false, 'requires_cycle_integration'=>true,
    ];
}

function fcc90_iso_time($value): ?DateTimeImmutable {
    if(!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/D', $value)) return null;
    try { $date = new DateTimeImmutable($value); } catch(Throwable $e) { return null; }
    if($date->format('Y-m-d\TH:i:s') !== substr($value, 0, 19)) return null;
    return $date;
}

/** Consume server-owned, identity-checked normalized FLP360 facts. Unknown never becomes zero. */
function fcc90_cc_evidence(array $facts, DateTimeImmutable $now): array {
    $now = $now->setTimezone(new DateTimeZone('Europe/Zagreb'));
    $start = $now->modify('first day of this month')->setTime(0,0);
    $allowed = [$start->format('Y-m-01'), $start->modify('-1 month')->format('Y-m-01'), $start->modify('-2 months')->format('Y-m-01')];
    $result=['personal_1'=>false,'active_months'=>0,'usable_periods'=>[],'issues'=>[]];
    if(empty($facts['identity_verified']) || !empty($facts['identity_conflict']) || !preg_match('/^\d{12}$/D', (string)($facts['fbo_id']??''))) {
        $result['issues'][]='identity_not_verified'; return $result;
    }
    $periods=[];
    foreach(($facts['metrics']??[]) as $metric) {
        if(!is_array($metric)) continue;
        $period=(string)($metric['period_month']??'');
        if(!in_array($period,$allowed,true)) continue;
        if(isset($periods[$period])) { $periods[$period]=null; $result['issues'][]='duplicate_period'; continue; }
        // Use array_key_exists here: an invalid duplicate period must not be reinstated.
        if(array_key_exists($period,$periods)) continue;
        $periods[$period]=$metric;
    }
    foreach($periods as $period=>$metric) {
        if(!$metric) continue;
        $importId=$metric['source_import_id']??null;
        $completed=fcc90_iso_time($metric['source_completed_at']??null);
        $active=$metric['is_4cc_active']??null;
        $personal=$metric['personal_cc']??null;
        $validPersonal=$personal===null || ((is_float($personal)||is_int($personal))&&is_finite((float)$personal)&&$personal>=0);
        if(($metric['fbo_id']??'')!==$facts['fbo_id'] || ($metric['region_verified']??false)!==true
            || !is_int($importId)||$importId<1 || ($metric['import_status']??'')!=='completed'
            || !in_array($metric['report_kind']??'', ['downline','four_cc_active'],true)
            || !$completed || $completed>$now || ($metric['conflict']??false)
            || !in_array($active,[true,false,null],true) || !$validPersonal) {
            $result['issues'][]='invalid_source:'.$period; continue;
        }
        $checked=$completed;
        $sync=fcc90_iso_time($metric['last_successful_check_at']??null);
        if($sync && $sync<=$now && $sync>=$completed && ($metric['checked_import_id']??null)===$importId && ($metric['check_hash_matches']??false)===true) $checked=$sync;
        if($period===$allowed[0]) {
            if($now->getTimestamp()-$checked->getTimestamp()>72*3600) { $result['issues'][]='stale_current_period'; continue; }
        } elseif(($metric['is_closed']??false)!==true || $completed < new DateTimeImmutable($period.' +1 month',new DateTimeZone('Europe/Zagreb'))) {
            $result['issues'][]='unconfirmed_closed_period:'.$period; continue;
        }
        $result['usable_periods'][]=$period;
        if($personal!==null && $personal>=1.0) $result['personal_1']=true;
        if($active===true) $result['active_months']++;
    }
    $result['issues']=array_values(array_unique($result['issues']));
    return $result;
}

/**
 * Advisory evaluator. Inputs MUST be server-owned snapshots, never POST data.
 * It proposes at most one level and never changes a cycle or official Forever rank.
 */
function fcc90_readiness(array $facts, DateTimeImmutable $now): array {
    $catalog=fcc90_catalog();
    $uid=$facts['user_id']??0; $cycle=$facts['cycle_id']??''; $current=$facts['accepted_level']??1;
    if(!is_int($uid)||$uid<1||!is_string($cycle)||$cycle===''||!is_int($current)||$current<1||$current>4) throw new InvalidArgumentException(fcc_t('Invalid FCC90 identity/cycle.'));
    $rows=[]; $ids=[]; $days=[]; $issues=[];
    foreach(($facts['outcomes']??[]) as $row) {
        if(!is_array($row)||($row['user_id']??null)!==$uid||($row['cycle_id']??null)!==$cycle) continue;
        if(($row['status']??'')!=='done') continue;
        $position=$row['position']??null; $id=$row['id']??null;
        $date=fcc90_iso_time($row['completed_at']??null);
        if(!is_int($position)||$position<1||$position>90||!is_int($id)||$id<1||!$date||$date>$now
            || ($row['task_id']??'')!==$catalog['tasks'][$position-1]['id']
            || ($row['content_version']??null)!==$catalog['tasks'][$position-1]['content_version']
            || !in_array($row['mode']??'', ['applied','practice'],true)
            || !in_array($row['output_status']??'', $catalog['tasks'][$position-1]['completion']['output_statuses'],true)
            || ($row['self_check_passed']??false)!==true) { $issues[]='invalid_outcome'; continue; }
        if(isset($ids[$id]) || isset($rows[$position])) { $issues[]='duplicate_outcome'; continue; }
        $day=$date->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Y-m-d');
        if(isset($days[$day])) { $issues[]='multiple_steps_same_local_day'; continue; }
        $days[$day]=true;
        $ids[$id]=true;
        $rows[$position]=$row;
    }
    $contiguous=[];
    for($i=1;$i<=90&&isset($rows[$i]);$i++) $contiguous[$i]=$rows[$i];
    if(count($contiguous)!==count($rows)) $issues[]='sequence_gap';
    $lastTime=null;
    foreach($contiguous as $row) {
        $time=fcc90_iso_time($row['completed_at']);
        if($lastTime!==null && $time<=$lastTime) $issues[]='sequence_time_order';
        $lastTime=$time;
    }
    $cc=fcc90_cc_evidence($facts,$now);
    $next=min(4,$current+1); $rule=$catalog['levels'][$next-1];
    $window=array_slice($contiguous,-$rule['window'],null,true);
    $applied=0; $external=0; $mentoring=0; $cores=[]; $interactions=[];
    foreach($window as $position=>$row) {
        if($row['mode']!=='applied'||$row['output_status']==='practised') continue;
        $task=$catalog['tasks'][$position-1];
        $actual=in_array($row['output_status'],['sent','performed'],true);
        $record=is_int($row['interaction_record_id']??null)&&$row['interaction_record_id']>0;
        // A linked record is a user's report, not proof of a reply, sale or CC.
        $contact=$actual&&$record&&($row['interaction_owner_id']??null)===$uid&&($row['contact_permission_valid']??false)===true;
        if($record&&isset($interactions[$row['interaction_record_id']])) $contact=false;
        if($contact) $interactions[$row['interaction_record_id']]=true;
        $mentor=$contact&&($row['mentoring_performed']??false)===true&&($row['mentee_consent_verified']??false)===true
            && in_array($position,[58,61,65,67,70,71,73,84],true);
        if($task['completion']['default_mode']==='practice'&&!$mentor) continue;
        $applied++; $cores[$task['core']]=true;
        if($contact&&$task['completion']['external_event_eligible']) $external++;
        if($mentor) $mentoring++;
    }
    $checks=[
        'enrolled'=>($facts['education_access']??false)===true,
        'valid_sequence'=>!$issues,
        'steps'=>count($contiguous)>=$rule['min_completed'],
        'applied'=>$applied>=$rule['min_applied'],
        'external'=>$external>=$rule['min_external'],
        'core_coverage'=>count($cores)>=$rule['min_cores'],
        'mentoring'=>$mentoring>=$rule['min_mentoring'],
        'willing_mentee'=>empty($rule['requires_willing_mentee'])||($facts['has_willing_mentee']??false)===true,
        'cc'=>match($rule['cc_rule']) {'personal_1'=>$cc['personal_1'],'official_active_1'=>$cc['active_months']>=1,'official_active_2'=>$cc['active_months']>=2,default=>true},
    ];
    $ready=$current<4&&!in_array(false,$checks,true);
    return ['policy_version'=>$catalog['policy']['id'],'accepted_level'=>$current,'proposed_level'=>$ready?$next:null,
        'at_highest_level'=>$current===4,'requires_acceptance'=>$ready,'completed'=>count($contiguous),'next_position'=>count($contiguous)<90?count($contiguous)+1:null,
        'checks'=>$checks,'missing'=>array_keys(array_filter($checks,static fn($v)=>!$v)),
        'counts'=>['window'=>count($window),'applied'=>$applied,'external'=>$external,'core_coverage'=>count($cores),'mentoring'=>$mentoring],
        'cc'=>$cc,'issues'=>array_values(array_unique($issues)), 'no_state_changed'=>true];
}
