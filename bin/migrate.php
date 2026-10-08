<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$pdo = App\Core\Database::getInstance();
if ((int) $pdo->query("SELECT GET_LOCK('tomer_crm_migrate',10)")->fetchColumn() !== 1) { throw new RuntimeException('Migration lock unavailable.'); }
try {
    $tables=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('schema_migrations', $tables, true) && $tables !== []) {
        throw new RuntimeException('Existing unversioned database detected. Back it up and use a separately reviewed legacy migration. No tables changed.');
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(100) NOT NULL PRIMARY KEY, checksum CHAR(64) NOT NULL, installed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    foreach (glob(BASE_PATH . '/database/migrations/*.sql') as $file) {
        $version=basename($file,'.sql'); $sql=file_get_contents($file); $checksum=hash('sha256',$sql);
        $q=$pdo->prepare('SELECT checksum FROM schema_migrations WHERE version=?'); $q->execute([$version]); $old=$q->fetchColumn();
        if ($old !== false) {
            if (!hash_equals($old,$checksum)) { throw new RuntimeException('Previously applied migration changed: '.$version); }
            continue;
        }
        // MySQL DDL commits implicitly. A partial failure must be investigated, not auto-retried.
        $pdo->exec($sql);
        $q=$pdo->prepare('INSERT INTO schema_migrations(version,checksum) VALUES(?,?)'); $q->execute([$version,$checksum]);
        echo 'Installed '.$version.PHP_EOL;
    }
    echo 'Database schema is current.'.PHP_EOL;
} finally { $pdo->query("SELECT RELEASE_LOCK('tomer_crm_migrate')"); }
