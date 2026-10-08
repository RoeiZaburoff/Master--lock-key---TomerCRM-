<?php
declare(strict_types=1);

namespace App\Modules\Reports;

use App\Core\QueryBuilder;

class ReportService
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function generate(array $filters): array
    {
        $statusFilter    = $filters['status_filter'] ?? 'completed';
        $where           = [];
        $params          = [];

        if ($statusFilter === 'deposit') {
            $where[]         = 'j.deposit_received = 1';
            $dateCol         = 'DATE(j.scheduled_date)';
            $reportingDateEx = 'DATE(j.scheduled_date)';
            $orderByEx       = 'COALESCE(j.scheduled_date, j.created_at) DESC';
        } elseif ($statusFilter === 'all') {
            $where[]         = "(j.status = 'completed' OR j.deposit_received = 1)";
            $dateCol         = 'COALESCE(DATE(j.completed_at), DATE(j.scheduled_date), DATE(j.created_at))';
            $reportingDateEx = 'COALESCE(DATE(j.completed_at), DATE(j.scheduled_date), DATE(j.created_at))';
            $orderByEx       = 'COALESCE(j.completed_at, j.scheduled_date, j.created_at) DESC';
        } else {
            $where[]         = "j.status = 'completed'";
            $dateCol         = 'DATE(j.completed_at)';
            $reportingDateEx = 'DATE(j.completed_at)';
            $orderByEx       = 'j.completed_at DESC';
        }

        $where[] = '1=1';

        if (!empty($filters['date_from'])) {
            $where[]              = "$dateCol >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]            = "$dateCol <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['lead_source_id'])) {
            $where[]          = 'j.lead_source_id = :ls_id';
            $params[':ls_id'] = (int) $filters['lead_source_id'];
        }
        if (!empty($filters['contractor_id'])) {
            $where[]         = 'j.contractor_id = :c_id';
            $params[':c_id'] = (int) $filters['contractor_id'];
        }
        if (!empty($filters['company_id'])) {
            $where[]            = 'j.company_id = :comp_id';
            $params[':comp_id'] = (int) $filters['company_id'];
        }

        $compJoin          = "LEFT JOIN companies comp ON comp.company_id = j.company_id";
        $compCol           = "COALESCE(comp.name,'—') AS company_name,";
        $depositCol        = 'j.deposit_amount,';
        $depositReceivedCol = 'j.deposit_received,';

        $sql = "SELECT
                    j.job_id, j.status, j.completed_at, j.scheduled_date,
                    {$reportingDateEx} AS reporting_date,
                    {$depositReceivedCol}
                    c.full_name AS customer_name,
                    {$compCol}
                    COALESCE(ls.name,'Direct') AS lead_source_name,
                    COALESCE(co.full_name,'Owner') AS contractor_name,
                    {$depositCol}
                    COALESCE(jf.job_total, j.job_total)         AS job_total,
                    COALESCE(jf.material_cost, 0)               AS material_cost,
                    COALESCE(jf.lead_fee, 0)                    AS lead_fee,
                    COALESCE(jf.contractor_share, 0)            AS contractor_share,
                    COALESCE(jf.gross_profit, 0)                AS gross_profit,
                    COALESCE(jf.net_profit, 0)                  AS net_profit,
                    COALESCE(jf.cc_fee, 0)                      AS cc_fee,
                    COALESCE(jf.contractor_share, 0)            AS tech_payout,
                    COALESCE(jf.net_profit, 0)                  AS provider_profit,
                    COALESCE(jf.material_tech, 0)               AS parts_by_tech,
                    COALESCE(jf.material_provider, 0)           AS parts_by_company
                FROM jobs j
                JOIN customers c ON c.customer_id = j.customer_id
                {$compJoin}
                LEFT JOIN lead_sources ls ON ls.lead_source_id = j.lead_source_id
                LEFT JOIN contractors co ON co.contractor_id = j.contractor_id
                LEFT JOIN job_financials jf ON jf.job_id = j.job_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$orderByEx}";

        $rows = $this->db->raw($sql, $params);

        $totals = [
            'job_total'        => 0.0,
            'deposit_amount'   => 0.0,
            'material_cost'    => 0.0,
            'lead_fee'         => 0.0,
            'contractor_share' => 0.0,
            'gross_profit'     => 0.0,
            'net_profit'       => 0.0,
            'cc_fee'           => 0.0,
            'parts_by_tech'    => 0.0,
            'parts_by_company' => 0.0,
        ];
        foreach ($rows as $row) {
            foreach ($totals as $key => $_) {
                $totals[$key] += (float) ($row[$key] ?? 0);
            }
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    public function getFilterOptions(): array
    {
        $companies = $this->db->table('companies')->where('is_active', 1)->orderBy('name')->get();
        return [
            'lead_sources' => $this->db->table('lead_sources')->orderBy('name')->get(),
            'contractors'  => $this->db->table('contractors')->orderBy('full_name')->get(),
            'companies'    => $companies,
        ];
    }


}
