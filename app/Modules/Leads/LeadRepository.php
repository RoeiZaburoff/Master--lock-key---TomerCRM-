<?php
declare(strict_types=1);

namespace App\Modules\Leads;

use App\Contracts\RepositoryInterface;
use App\Core\QueryBuilder;

class LeadRepository implements RepositoryInterface
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    private function companyJoin(): string
    {
        return "LEFT JOIN companies comp ON comp.company_id = j.company_id";
    }

    private function companyCols(): string
    {
        return ",\n                    comp.company_id AS company_id,
                    comp.name AS company_name,
                    comp.phone AS company_phone,
                    comp.address AS company_address,
                    comp.logo_path AS company_logo";
    }

    private function companyColsShort(): string
    {
        return ", comp.name AS company_name, comp.logo_path AS company_logo";
    }

    public function findById(int $id): array|null
    {
        return $this->db->rawFirst(
            "SELECT j.*,
                    c.full_name AS customer_name,
                    c.phone AS customer_phone,
                    c.address_line1 AS customer_address_line1,
                    c.address_line2 AS customer_address_line2,
                    c.city AS customer_city,
                    c.state AS customer_state,
                    c.zip AS customer_zip,
                    ls.name AS lead_source_name, ls.fee_type, ls.fee_value,
                    co.full_name AS contractor_name, co.split_type, co.split_value
                    {$this->companyCols()}
             FROM jobs j
             JOIN customers c ON c.customer_id = j.customer_id
             LEFT JOIN lead_sources ls ON ls.lead_source_id = j.lead_source_id
             LEFT JOIN contractors co ON co.contractor_id = j.contractor_id
             {$this->companyJoin()}
             WHERE j.job_id = :id",
            [':id' => $id]
        );
    }

    public function findAll(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'deposit') {
                $where[] = 'j.deposit_received = 1';
            } elseif ($filters['status'] === 'active') {
                $where[] = "j.status IN ('scheduled','progress','in_progress')";
            } else {
                $where[]           = 'j.status = :status';
                $params[':status'] = $filters['status'];
            }
        }

        if (!empty($filters['period'])) {
            $today      = date('Y-m-d');
            $isComplete = (!empty($filters['status']) && $filters['status'] === 'completed');
            $periodCol  = $isComplete ? 'DATE(j.completed_at)' : 'j.scheduled_date';

            if ($filters['period'] === 'weekly') {
                [$s, $e] = week_bounds();
                $where[]       = "{$periodCol} BETWEEN :ps AND :pe";
                $params[':ps'] = $s;
                $params[':pe'] = $e;
            } elseif ($filters['period'] === 'monthly') {
                $where[]       = "{$periodCol} BETWEEN :ps AND :pe";
                $params[':ps'] = date('Y-m-01');
                $params[':pe'] = date('Y-m-t');
            } elseif ($filters['period'] === 'daily') {
                $where[]       = "{$periodCol} = :pd";
                $params[':pd'] = $today;
            }
        }

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(c.full_name       LIKE :sq1
                      OR c.phone           LIKE :sq2
                      OR j.address         LIKE :sq3
                      OR j.city            LIKE :sq4
                      OR j.state           LIKE :sq5
                      OR j.zip             LIKE :sq6
                      OR j.job_type        LIKE :sq7
                      OR CAST(j.job_total AS CHAR) LIKE :sq8)';
            $params[':sq1'] = $s;
            $params[':sq2'] = $s;
            $params[':sq3'] = $s;
            $params[':sq4'] = $s;
            $params[':sq5'] = $s;
            $params[':sq6'] = $s;
            $params[':sq7'] = $s;
            $params[':sq8'] = $s;
        }

        $companyNameCol = "comp.name AS company_name, comp.logo_path AS company_logo";

        $sql = "SELECT j.*, c.full_name AS customer_name,
                       c.phone AS customer_phone,
                       COALESCE(NULLIF(j.address,''), c.address_line1) AS address_line1,
                       COALESCE(NULLIF(j.city,''), c.city) AS city,
                       COALESCE(NULLIF(j.state,''), c.state) AS state,
                       COALESCE(NULLIF(j.zip,''), c.zip) AS zip,
                       COALESCE(ls.name,'Direct') AS lead_source_name,
                       COALESCE(co.full_name,'Owner') AS contractor_name,
                       {$companyNameCol}
                FROM jobs j
                JOIN customers c ON c.customer_id = j.customer_id
                LEFT JOIN lead_sources ls ON ls.lead_source_id = j.lead_source_id
                LEFT JOIN contractors co ON co.contractor_id = j.contractor_id
                {$this->companyJoin()}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY j.scheduled_date DESC, j.scheduled_time DESC";

        return $this->db->raw($sql, $params);
    }

    public function findForMonth(int $year, int $month): array
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $drCol = ', j.deposit_received';
        return $this->db->raw(
            "SELECT j.job_id, j.scheduled_date, j.scheduled_time, j.job_type, j.status{$drCol},
                    c.full_name AS customer_name
                    {$this->companyColsShort()}
             FROM jobs j
             JOIN customers c ON c.customer_id = j.customer_id
             {$this->companyJoin()}
             WHERE j.scheduled_date BETWEEN :from AND :to
               AND j.status NOT IN ('canceled','cancelled','estimate')
             ORDER BY j.scheduled_date ASC, j.scheduled_time ASC",
            [':from' => $from, ':to' => $to]
        );
    }

    public function findForWeek(string $monday, string $sunday): array
    {
        $drCol = ', j.deposit_received';
        return $this->db->raw(
            "SELECT j.job_id, j.scheduled_date, j.scheduled_time, j.job_type, j.status{$drCol},
                    c.full_name AS customer_name
                    {$this->companyColsShort()}
             FROM jobs j
             JOIN customers c ON c.customer_id = j.customer_id
             {$this->companyJoin()}
             WHERE j.scheduled_date BETWEEN :from AND :to
               AND j.status NOT IN ('canceled','cancelled','estimate')
             ORDER BY j.scheduled_date ASC, j.scheduled_time ASC",
            [':from' => $monday, ':to' => $sunday]
        );
    }

    public function create(array $data): int
    {
        return $this->db->table('jobs')->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table('jobs')->where('job_id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->table('jobs')->where('job_id', $id)->delete();
    }

    public function getMaterials(int $jobId): array
    {
        return $this->db->raw(
            "SELECT jm.*,
                    i.name        AS catalog_name,
                    i.fcc_id,
                    i.part_number,
                    m.name        AS manufacturer_name
             FROM job_materials jm
             LEFT JOIN inventory     i ON i.inventory_id    = jm.inventory_id
             LEFT JOIN manufacturers m ON m.manufacturer_id = i.manufacturer_id
             WHERE jm.job_id = :id
             ORDER BY jm.id ASC",
            [':id' => $jobId]
        );
    }

    public function insertMaterial(array $data): int
    {
        return $this->db->table('job_materials')->insert($data);
    }

    public function deleteMaterials(int $jobId): bool
    {
        return $this->db->table('job_materials')->where('job_id', $jobId)->delete();
    }

    public function decrementStock(int $inventoryId, float $qty): void
    {
        $this->db->rawExecute(
            "UPDATE inventory SET quantity = GREATEST(0, quantity - :qty) WHERE inventory_id = :id",
            [':qty' => $qty, ':id' => $inventoryId]
        );
    }

    public function getFinancials(int $jobId): array|null
    {
        return $this->db->table('job_financials')->where('job_id', $jobId)->first();
    }

    public function getMeta(int $jobId): array
    {
        $rows = $this->db->raw(
            "SELECT meta_key, meta_value FROM job_meta WHERE job_id = :id",
            [':id' => $jobId]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[$row['meta_key']] = $row['meta_value'];
        }
        return $out;
    }

    public function upsertMeta(int $jobId, string $key, ?string $value): void
    {
        $this->db->rawExecute(
            "INSERT INTO job_meta (job_id, meta_key, meta_value)
             VALUES (:job_id, :key, :val)
             ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
            [':job_id' => $jobId, ':key' => $key, ':val' => $value]
        );
    }

    public function saveFinancials(array $data): void
    {
        $this->db->rawExecute(
            "INSERT INTO job_financials
                (job_id, job_total, material_cost, material_tech, material_provider,
                 lead_fee, contractor_share, gross_profit, net_profit, cc_fee, calculation_policy)
             VALUES
                (:job_id, :job_total, :material_cost, :material_tech, :material_provider,
                 :lead_fee, :contractor_share, :gross_profit, :net_profit, :cc_fee, :calculation_policy)
             ON DUPLICATE KEY UPDATE
                job_total         = VALUES(job_total),
                material_cost     = VALUES(material_cost),
                material_tech     = VALUES(material_tech),
                material_provider = VALUES(material_provider),
                lead_fee          = VALUES(lead_fee),
                contractor_share  = VALUES(contractor_share),
                gross_profit      = VALUES(gross_profit),
                net_profit        = VALUES(net_profit),
                cc_fee            = VALUES(cc_fee),
                calculation_policy = VALUES(calculation_policy),
                calculated_at     = CURRENT_TIMESTAMP",
            $data
        );
    }
}
