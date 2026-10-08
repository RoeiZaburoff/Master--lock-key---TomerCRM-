<?php
declare(strict_types=1);
namespace App\Modules\Integrations;
use App\Core\Database;

final class EventWorker
{
    public function run(int $limit=25): array
    {
        $pdo=Database::getInstance(); $limit=max(1,min(100,$limit));
        $ids=$pdo->query("SELECT event_id FROM integration_events WHERE status IN ('pending','failed') AND attempts<5 ORDER BY event_id LIMIT $limit")->fetchAll(\PDO::FETCH_COLUMN);
        $counts=['processed'=>0,'failed'=>0];
        foreach ($ids as $id) {
            try {
                $pdo->beginTransaction();
                $q=$pdo->prepare('SELECT * FROM integration_events WHERE event_id=? FOR UPDATE'); $q->execute([$id]); $event=$q->fetch();
                if (!$event || $event['status']==='processed' || (int)$event['attempts']>=5) { $pdo->commit(); continue; }
                $payload=json_decode($event['payload'],true,32,JSON_THROW_ON_ERROR);
                $createInquiry=$event['source']==='wordpress';
                if ($event['source']==='retell') {
                    $rank=['call_started'=>1,'call_ended'=>2,'call_analyzed'=>3][$payload['type']];
                    $q=$pdo->prepare("SELECT company_id,agent_id FROM voice_calls WHERE provider='retell' AND external_id=?"); $q->execute([$payload['call_id']]); $old=$q->fetch();
                    if ($old && ((int)$old['company_id']!==(int)$event['company_id'] || $old['agent_id']!==$payload['agent_id'])) { throw new \RuntimeException('Call identity mismatch.'); }
                    $q=$pdo->prepare("INSERT INTO voice_calls(provider,external_id,company_id,agent_id,from_number,to_number,event_rank,status,summary,transcript)
                        VALUES('retell',?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
                        from_number=IF(VALUES(event_rank)>=event_rank,VALUES(from_number),from_number),
                        to_number=IF(VALUES(event_rank)>=event_rank,VALUES(to_number),to_number),
                        status=IF(VALUES(event_rank)>=event_rank,VALUES(status),status),
                        summary=IF(VALUES(event_rank)>=event_rank,VALUES(summary),summary),
                        transcript=IF(VALUES(event_rank)>=event_rank,VALUES(transcript),transcript),
                        event_rank=GREATEST(event_rank,VALUES(event_rank))");
                    $q->execute([$payload['call_id'],$event['company_id'],$payload['agent_id'],$payload['from_number'],$payload['to_number'],$rank,$payload['type'],$payload['summary'],$payload['transcript']]);
                    $createInquiry=$payload['type']==='call_analyzed';
                }
                if ($createInquiry) {
                    $q=$pdo->prepare('INSERT INTO inquiries(event_id,company_id,source,contact,priority_hint) VALUES(?,?,?,?,?)');
                    $q->execute([$id,$event['company_id'],$event['source'],json_encode($payload['contact'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$payload['priority_hint']]);
                }
                $q=$pdo->prepare("UPDATE integration_events SET status='processed',attempts=attempts+1,processed_at=UTC_TIMESTAMP(),last_error=NULL WHERE event_id=?"); $q->execute([$id]);
                $pdo->commit(); $counts['processed']++;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $q=$pdo->prepare("UPDATE integration_events SET status='failed',attempts=attempts+1,last_error=? WHERE event_id=?"); $q->execute([get_class($e),$id]);
                $counts['failed']++;
            }
        }
        return $counts;
    }
}
