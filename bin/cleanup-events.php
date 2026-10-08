<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$days=max(7,(int)App\Core\RuntimeConfig::get('INTEGRATION_RETENTION_DAYS',30));
$pdo=App\Core\Database::getInstance();
// Preserve idempotency tombstones. Keep business inquiry records until an explicit retention policy is applied.
$pdo->exec("UPDATE integration_events SET payload=JSON_OBJECT() WHERE status='processed' AND processed_at<UTC_TIMESTAMP()-INTERVAL $days DAY");
$pdo->exec("UPDATE voice_calls SET transcript=NULL WHERE updated_at<UTC_TIMESTAMP()-INTERVAL $days DAY");
$pdo->exec("DELETE FROM auth_attempts WHERE window_start<UTC_TIMESTAMP()-INTERVAL 1 DAY");
echo 'Event payloads and expired transcripts cleaned; inquiry records retained.'.PHP_EOL;
