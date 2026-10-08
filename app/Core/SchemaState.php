<?php
declare(strict_types=1);
namespace App\Core;
final class SchemaState
{
    public static function current(): bool
    {
        $installed=Database::getInstance()->query('SELECT version,checksum FROM schema_migrations')->fetchAll(\PDO::FETCH_KEY_PAIR);
        foreach (glob(BASE_PATH.'/database/migrations/*.sql') as $file) {
            if (($installed[basename($file,'.sql')] ?? '')!==hash_file('sha256',$file)) { return false; }
        }
        return true;
    }
}
