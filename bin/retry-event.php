<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$id=filter_var($argv[1] ?? '',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$id) { throw new RuntimeException('Supply a failed event ID after diagnosing its cause.'); }
$q=App\Core\Database::getInstance()->prepare("UPDATE integration_events SET status='pending',attempts=0,last_error=NULL WHERE event_id=? AND status='failed'");
$q->execute([$id]); echo 'Requeued: '.$q->rowCount().PHP_EOL;
