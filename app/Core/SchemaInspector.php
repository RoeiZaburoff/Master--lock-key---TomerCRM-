<?php
declare(strict_types=1);

namespace App\Core;

class SchemaInspector
{
    private static array $cache = [];

    public static function tableExists(string $table): bool
    {
        if (isset(self::$cache[$table])) {
            return self::$cache[$table];
        }
        try {
            $db     = new QueryBuilder();
            $result = $db->rawFirst("SHOW TABLES LIKE '$table'");
            self::$cache[$table] = ($result !== null);
        } catch (\Throwable $e) {
            self::$cache[$table] = false;
        }
        return self::$cache[$table];
    }

    public static function columnExists(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        try {
            $db     = new QueryBuilder();
            $result = $db->rawFirst(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = :tbl
                   AND COLUMN_NAME  = :col",
                [':tbl' => $table, ':col' => $column]
            );
            self::$cache[$key] = ($result !== null);
        } catch (\Throwable $e) {
            self::$cache[$key] = false;
        }
        return self::$cache[$key];
    }
}
