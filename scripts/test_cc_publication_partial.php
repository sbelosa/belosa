<?php
/* Custom code: FC-2026-10-14: Exercise real publication transactions against an isolated memory fixture. */
if(PHP_SAPI!=='cli')exit(1);
define('ALTUMCODE',true);
define('APP_PATH',dirname(__DIR__).'/app/');
$GLOBALS['checks']=0;
function check(bool $ok,string $label): void {if(!$ok)throw new RuntimeException($label);$GLOBALS['checks']++;}
class MemoryTransactions {
    private array $before=[];
    function begin_transaction(): void {$this->before=$GLOBALS['fixture'];}
    function commit(): void {}
    function rollback(): void {$GLOBALS['fixture']=$this->before;}
}
function database(): MemoryTransactions {static $db;return $db??=new MemoryTransactions();}
function forever_business_user_fbo_sql_expression(): string {return 'fixture_fbo';}
function fcc_partner_today(): string {return gmdate('Y-m-d');}
function fcc_partner_one(string $sql,array $p=[]): ?array {
    $f=&$GLOBALS['fixture'];
    if(str_contains($sql,'information_schema.TABLES'))return ['exists'=>1];
    if(str_contains($sql,'GET_LOCK'))return ['ok'=>1];
    if(str_contains($sql,'LAST_INSERT_ID'))return ['id'=>$f['last_id']];
    if(str_contains($sql,'FROM fcc_cc_batches'))return $f['batches'][$p[0]]??null;
    if(str_contains($sql,'FROM forever_business_metrics'))return $f['metrics'][$p[0]]??null;
    if(str_contains($sql,'SELECT title FROM fcc_cc_snapshots'))return ['title'=>'Assistant Supervisor'];
    if(str_contains($sql,'FROM fcc_cc_snapshots'))return $f['snapshots'][$p[0]]??null;
    throw new RuntimeException('Unexpected read: '.$sql);
}
function fcc_partner_rows(string $sql,array $p=[]): array {
    if(str_contains($sql,'FROM users'))return array_map(fn($id)=>['fbo'=>$id],array_keys($GLOBALS['fixture']['metrics']));
    throw new RuntimeException('Unexpected rows: '.$sql);
}
function fcc_partner_query(string $sql,array $p=[]): object {
    $f=&$GLOBALS['fixture'];
    if(str_contains($sql,'RELEASE_LOCK'))return (object)['affected_rows'=>0];
    if(str_contains($sql,'INSERT INTO fcc_cc_batches')){$f['last_id']++;$f['batches'][$p[0]]=['id'=>$f['last_id']];}
    elseif(str_contains($sql,'INSERT INTO fcc_cc_snapshots')){
        $f['last_id']++;
        $f['snapshots'][$p[1]]=array_combine(['batch_id','fbo_id','period_month','personal_cc','total_cc','total_active_cc','is_4cc_active','title','captured_at','previous_id'],$p)+['id'=>$f['last_id']];
    }elseif(str_contains($sql,'INSERT IGNORE INTO fcc_cc_events'))$f['events'][$p[0]]=$p;
    else throw new RuntimeException('Unexpected write: '.$sql);
    return (object)['affected_rows'=>1];
}
require APP_PATH.'helpers/fcc_cc.php';
$period=gmdate('Y-m-01');
$a='111111111111';$b='222222222222';$c='333333333333';
$record=fn($id,$n,$active)=>['fbo_id'=>$id,'personal_cc'=>$n,'total_cc'=>$n,'total_active_cc'=>$n,'is_4cc_active'=>$active];
$old=fn($id,$n,$active,$key)=>$record($id,$n,$active)+['id'=>$key,'period_month'=>$period,'title'=>'Assistant Supervisor','captured_at'=>'2026-10-06 04:46:33'];
$fresh=fn()=>['metrics'=>[$a=>$record($a,4.054,1)+['title'=>'Assistant Supervisor'],$b=>$record($b,0.750,0)+['title'=>'Assistant Supervisor'],$c=>$record($c,2.0,0)+['title'=>'Assistant Supervisor']],
    'snapshots'=>[$a=>$old($a,2.008,0,1),$b=>$old($b,0.750,0,2),$c=>$old($c,2.0,0,3)],'batches'=>[],'events'=>[],'last_id'=>3];
$expected=[$record($a,4.054,1),$record($b,0.750,0)];
$GLOBALS['fixture']=$fresh();$beforeC=$GLOBALS['fixture']['snapshots'][$c];
$r=fcc_cc_publish($period,$expected,str_repeat('a',64),[$c]);
check($r['records']===2&&$r['preserved']===1&&!$r['complete'],'Partial outcome remains explicitly incomplete.');
check($r['changed']===1&&$r['events']===2,'Confirmed growth and activity produce the standard events exactly once.');
check($GLOBALS['fixture']['snapshots'][$a]['personal_cc']===4.054,'Confirmed value replaces stale dashboard history.');
check($GLOBALS['fixture']['snapshots'][$c]===$beforeC,'Unavailable identity remains byte for byte unchanged.');
$same=$GLOBALS['fixture'];$r=fcc_cc_publish($period,$expected,str_repeat('a',64),[$c]);
check($r['duplicate']&&$GLOBALS['fixture']===$same,'Retry is idempotent.');
foreach([
    [$expected,[], 'An omitted registered identity fails closed.'],
    [$expected,[$c,$c], 'Repeated unconfirmed identity is rejected.'],
    [$expected,[$a,$c], 'Confirmed and unconfirmed partitions cannot overlap.'],
    [$expected,['999999999999'], 'An unknown preservation identity is rejected.'],
    [array_merge($expected,[$record('999999999999',1.0,0)]),[$c], 'Partial publication cannot add unregistered identities.'],
    [[$record($a,4.055,1),$record($b,0.750,0)],[$c], 'A metric conflict rolls back all publication writes.'],
    [[$record($a,-1,1),$record($b,0.750,0)],[$c], 'Negative metrics are rejected.'],
] as [$values,$preserved,$label]){
    $GLOBALS['fixture']=$fresh();$before=$GLOBALS['fixture'];$rejected=false;
    try{fcc_cc_publish($period,$values,str_repeat('b',64),$preserved);}catch(InvalidArgumentException $e){$rejected=true;}
    check($rejected&&$GLOBALS['fixture']===$before,$label);
}
$GLOBALS['fixture']=$fresh();$r=fcc_cc_publish($period,[$record($a,4.054,1),$record($b,0.750,0),$record($c,2.0,0)],str_repeat('c',64));
check($r['complete']&&$r['records']===3&&$r['preserved']===0,'Original full publication remains compatible.');
echo json_encode(['status'=>'PASS','checks'=>$GLOBALS['checks'],'database'=>'isolated memory','network'=>false]).PHP_EOL;
/* /Custom code: FC-2026-10-14 */
