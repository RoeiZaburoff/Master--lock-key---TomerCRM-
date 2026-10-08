<?php
declare(strict_types=1);
use App\Core\RuntimeConfig as Config;
return [
    'host' => Config::get('DB_HOST', 'localhost'),
    'port' => (int) Config::get('DB_PORT', 3306),
    'dbname' => Config::get('DB_NAME', ''),
    'username' => Config::get('DB_USER', ''),
    'password' => Config::get('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
];
