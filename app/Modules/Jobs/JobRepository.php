<?php
declare(strict_types=1);

namespace App\Modules\Jobs;

use App\Contracts\RepositoryInterface;
use App\Core\QueryBuilder;

class JobRepository implements RepositoryInterface
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
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
             FROM jobs j
             JOIN customers c ON c.customer_id = j.customer_id
             LEFT JOIN lead_sources ls ON ls.lead_source_id = j.lead_source_id
             LEFT JOIN contractors co ON co.contractor_id = j.contractor_id
             WHERE j.job_id = :id",
            [':id' => $id]
        );
    }

    public function findAll(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]           = 'j.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[]       = '(c.full_name LIKE :s OR j.job_type LIKE :s2)';
            $s             = '%' . $filters['search'] . '%';
            $params[':s']  = $s;
            $params[':s2'] = $s;
        }

        $sql = "SELECT j.*, c.full_name AS customer_name,
                       COALESCE(ls.name,'Direct') AS lead_source_name,
                       COALESCE(co.full_name,'Owner') AS contractor_name
                FROM jobs j
                JOIN customers c ON c.customer_id = j.customer_id
                LEFT JOIN lead_sources ls ON ls.lead_source_id = j.lead_source_id
                LEFT JOIN contractors co ON co.contractor_id = j.contractor_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY j.scheduled_date DESC, j.scheduled_time DESC";

        return $this->db->raw($sql, $params);
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
             LEFT JOIN inventory     i ON i.inventory_id     = jm.inventory_id
             LEFT JOIN manufacturers m ON m.manufacturer_id  = i.manufacturer_id
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

    public function getFinancials(int $jobId): array|null
    {
        return $this->db->table('job_financials')->where('job_id', $jobId)->first();
    }

    public function saveFinancials(array $data): void
    {
        $this->db->rawExecute(
            "INSERT INTO job_financials
                (job_id, job_total, material_cost, material_tech, material_provider,
                 lead_fee, contractor_share, gross_profit, net_profit, cc_fee)
             VALUES
                (:job_id, :job_total, :material_cost, :material_tech, :material_provider,
                 :lead_fee, :contractor_share, :gross_profit, :net_profit, :cc_fee)
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
                calculated_at     = CURRENT_TIMESTAMP",
            $data
        );
    }

    public function decrementStock(int $inventoryId, float $qty): void
    {
        $this->db->rawExecute(
            "UPDATE inventory SET quantity = GREATEST(0, quantity - :qty) WHERE inventory_id = :id",
            [':qty' => $qty, ':id' => $inventoryId]
        );
    }
}
