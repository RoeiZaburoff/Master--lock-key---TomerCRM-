<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . '/storage');
require BASE_PATH . '/autoload.php';
require BASE_PATH . '/config/app.php';
set_exception_handler(function (Throwable $e): void {
    fwrite(STDERR, 'Command failed: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL);
    exit(1);
});
