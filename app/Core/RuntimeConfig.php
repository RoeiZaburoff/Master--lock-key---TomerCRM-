<?php
declare(strict_types=1);
namespace App\Core;

final class RuntimeConfig
{
    private static ?array $local = null;
    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$local === null) {
            $file = BASE_PATH . '/config/local.php';
            self::$local = is_file($file) ? require $file : [];
            if (!is_array(self::$local)) { throw new \RuntimeException('Invalid local configuration.'); }
        }
        $value = getenv($key);
        return $value !== false ? $value : (self::$local[$key] ?? $default);
    }
    public static function enabled(string $key, bool $default = false): bool
    {
        return filter_var(self::get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
}
