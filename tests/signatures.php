<?php
declare(strict_types=1);
define('BASE_PATH',dirname(__DIR__));require BASE_PATH.'/autoload.php';
use App\Modules\Integrations\Signatures;
function check(bool $ok,string $name): void { if (!$ok) { throw new RuntimeException($name); }echo 'PASS '.$name.PHP_EOL; }
$secret=str_repeat('test-key-',4);$raw="{\"event\":\"call_analyzed\",\"call\":{\"call_id\":\"fixture\"}}\n";
$stamp='1700000000000';$sig='v='.$stamp.',d='.hash_hmac('sha256',$raw.$stamp,$secret);
check(Signatures::retell($raw,$secret,$sig,(int)$stamp),'Retell format verified');
check(!Signatures::retell(trim($raw),$secret,$sig,(int)$stamp),'exact raw body required');
check(!Signatures::retell($raw,$secret,$sig,(int)$stamp+300001),'stale signature rejected');
check(!Signatures::retell($raw,$secret,$sig,(int)$stamp-300001),'future signature rejected');
check(!Signatures::retell($raw,'wrong',$sig,(int)$stamp),'wrong signing key rejected');
$ts='1700000000';$wp=hash_hmac('sha256',$ts.'.'.$raw,$secret);
check(Signatures::website($raw,$secret,$ts,$wp,(int)$ts),'website signature verified');
check(!Signatures::website($raw.'x',$secret,$ts,$wp,(int)$ts),'website body tampering rejected');
