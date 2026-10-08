<?php
declare(strict_types=1);
use App\Core\RuntimeConfig as Config;
define('APP_NAME', Config::get('APP_NAME', 'MasterLocknKey CRM'));
define('APP_BASE', rtrim((string) Config::get('APP_BASE', ''), '/'));
define('APP_TIMEZONE', Config::get('APP_TIMEZONE', 'America/New_York'));
define('APP_DEBUG', Config::enabled('APP_DEBUG'));
define('GOOGLE_PLACES_API_KEY', Config::get('GOOGLE_PLACES_API_KEY', ''));
date_default_timezone_set(APP_TIMEZONE);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
