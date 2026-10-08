<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$errors=[];
foreach (['pdo_mysql','mbstring','gd','zip','fileinfo'] as $ext) { if (!extension_loaded($ext)) { $errors[]='Missing extension: '.$ext; } }
foreach (['logos','inventory','pdf'] as $dir) { $p=STORAGE_PATH.'/'.$dir;if (!is_dir($p) || !is_writable($p)) { $errors[]='Storage is not writable: '.$dir; } }
$pdo=App\Core\Database::getInstance();
$versions=$pdo->query('SELECT version,checksum FROM schema_migrations')->fetchAll(PDO::FETCH_KEY_PAIR);
foreach (glob(BASE_PATH.'/database/migrations/*.sql') as $file) {
    if (($versions[basename($file,'.sql')] ?? '')!==hash_file('sha256',$file)) { $errors[]='Schema migration missing or changed: '.basename($file); }
}
echo 'PHP '.PHP_VERSION.PHP_EOL;
echo 'Voice integration: '.(App\Core\RuntimeConfig::enabled('RETELL_ENABLED')?'enabled':'disabled').PHP_EOL;
echo 'Website integration: '.(App\Core\RuntimeConfig::enabled('WEBSITE_ENABLED')?'enabled':'disabled').PHP_EOL;
foreach ($errors as $error) { fwrite(STDERR,$error.PHP_EOL); }
if ($errors) { exit(1); }
echo 'Runtime and schema checks passed.'.PHP_EOL;
