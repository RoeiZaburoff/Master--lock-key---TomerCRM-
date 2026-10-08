<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$name=$argv[1] ?? 'MasterLocknKey';
$result=(new App\Modules\Settings\CompanyService())->create(['name'=>$name]);
if (!$result['success']) { throw new RuntimeException('Company creation failed.'); }
echo 'Company ID: '.$result['id'].PHP_EOL;
