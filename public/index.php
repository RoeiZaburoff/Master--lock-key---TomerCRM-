<?php
declare(strict_types=1);
$parent = dirname(__DIR__);
define('BASE_PATH', is_file($parent . '/autoload.php') ? $parent : $parent . '/crm-private');
require BASE_PATH . '/bootstrap.php';
