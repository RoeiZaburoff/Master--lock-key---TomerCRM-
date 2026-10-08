<?php
declare(strict_types=1);
require dirname(__DIR__).'/bin/bootstrap.php';
if (getenv('TEST_ALLOW_DB_WRITE')!=='1' || App\Core\RuntimeConfig::enabled('REQUIRE_HTTPS',true)) { throw new RuntimeException('Disposable local database required.'); }
function check(bool $ok,string $name): void { if (!$ok) { throw new RuntimeException($name); }echo 'PASS '.$name.PHP_EOL; }
$pdo=App\Core\Database::getInstance();
$result=(new App\Modules\Integrations\EventWorker())->run(100);check($result['failed']===0,'event queue processing');
$count=(int)$pdo->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
check($count>=2,'website and voice inquiries created');
check((new App\Modules\Integrations\EventWorker())->run()['processed']===0,'queue replay does not create duplicate inquiries');
$call=$pdo->query("SELECT * FROM voice_calls WHERE external_id='fixture-call'")->fetch();check($call && $call['status']==='call_analyzed','out-of-order call events do not regress status');
check($call['transcript']===null,'transcript storage is off');
$inquiry=$pdo->query("SELECT * FROM inquiries WHERE source='wordpress' ORDER BY inquiry_id DESC LIMIT 1")->fetch();
$expected=(int)json_decode(file_get_contents('/tmp/mlk-test-ids.json'),true)['company_a'];
check((int)$inquiry['company_id']===$expected,'website key binds company and ignores supplied company ID');
$contact=json_decode($inquiry['contact'],true);$data=$contact+['priority'=>'normal'];
check(!(new App\Modules\Integrations\IntakeService())->promote((int)$inquiry['inquiry_id'],['address'=>''],1)['success'],'incomplete address blocks promotion');
$data['address']='300 Confirmed Road';$data['city']='Chicago';$data['state']='IL';$data['zip']='60603';
$service=new App\Modules\Integrations\IntakeService();$one=$service->promote((int)$inquiry['inquiry_id'],$data,1);$two=$service->promote((int)$inquiry['inquiry_id'],$data,1);
check($one['success'] && $one['id']===$two['id'],'human promotion is atomic and idempotent');
$q=$pdo->prepare('SELECT company_id FROM jobs WHERE job_id=?');$q->execute([$one['id']]);check((int)$q->fetchColumn()===$expected,'promoted job keeps trusted company binding');
