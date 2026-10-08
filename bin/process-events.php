<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$result=(new App\Modules\Integrations\EventWorker())->run((int)($argv[1] ?? 25));
echo json_encode($result,JSON_THROW_ON_ERROR).PHP_EOL;
exit($result['failed']>0?1:0);
