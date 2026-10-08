<?php
declare(strict_types=1);
namespace App\Modules\Integrations;
use App\Core\Database;

final class EventInbox
{
    public function accept(string $source, string $externalId, int $companyId, array $payload): array
    {
        $pdo=Database::getInstance();
        $q=$pdo->prepare('SELECT company_id FROM companies WHERE company_id=? AND is_active=1'); $q->execute([$companyId]);
        if (!$q->fetchColumn()) { throw new \InvalidArgumentException('Integration company is unavailable.'); }
        $json=json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
        $hash=hash('sha256',$json);
        try {
            $q=$pdo->prepare('INSERT INTO integration_events(source,external_id,company_id,payload,payload_hash) VALUES(?,?,?,?,?)');
            $q->execute([$source,$externalId,$companyId,$json,$hash]);
            return ['accepted'=>true,'duplicate'=>false,'event_id'=>(int)$pdo->lastInsertId()];
        } catch (\PDOException $e) {
            if ($e->getCode() !== '23000') { throw $e; }
            $q=$pdo->prepare('SELECT event_id,payload_hash,company_id FROM integration_events WHERE source=? AND external_id=?'); $q->execute([$source,$externalId]);
            $old=$q->fetch();
            if (!$old) { throw $e; }
            if ((int)$old['company_id']!==$companyId || !hash_equals($old['payload_hash'],$hash)) { throw new \DomainException('Event identifier was already used for different data.'); }
            return ['accepted'=>true,'duplicate'=>true,'event_id'=>(int)$old['event_id']];
        }
    }
}
