<?php
/* Custom code: FC-2026-10-13: Actual Coach regressions, synthetic context and transport only. */
if(PHP_SAPI !== 'cli') exit(1);
$root=dirname(__DIR__);
$cases=[
    'coach_intent_reference_test.php'=>[],
    'coach_medical_fields_test.php'=>[],
    'coach_education_fallback_test.php'=>[],
    'coach_language_preferences_test.php'=>[],
    'coach_feedback_anchor_test.php'=>['--require-audit-fields'],
    'coach_model_provenance_test.php'=>[],
    'coach_csrf_controller.php'=>['actual'],
];
$failures=[];
foreach($cases as $name=>$args) {
    $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/fcc_coach_tests/'.$name).' '.escapeshellarg($root);
    foreach($args as $arg) $command.=' '.escapeshellarg($arg);
    $output=[];$status=1;exec($command.' 2>&1',$output,$status);
    $decoded=json_decode(implode("\n",$output),true);
    $count=$decoded['checks_total']??$decoded['tests']??$decoded['cases']??$decoded['checks']??null;
    if(is_array($count))$count=count($count);
    echo json_encode(['fixture'=>$name,'status'=>$status===0?'PASS':'FAIL','checks'=>$count,'failures'=>$status===0?[]:$decoded??$output],JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE),"\n";
    if($status!==0)$failures[]=$name;
}
echo json_encode(['status'=>$failures?'FAIL':'PASS','fixtures'=>count($cases),'failed'=>$failures]),"\n";
exit($failures?1:0);
/* /Custom code: FC-2026-10-13 */
