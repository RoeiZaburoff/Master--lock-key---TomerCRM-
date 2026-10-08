<?php
declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Core\QueryBuilder;

class DashboardService
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function getLeadsForDate(string $date): array
    {
        return $this->db->raw(
            "SELECT j.*, c.full_name AS customer_name, c.phone AS customer_phone,
                    COALESCE(NULLIF(j.address,''), c.address_line1) AS address_line1,
                    COALESCE(NULLIF(j.city,''), c.city) AS city,
                    COALESCE(NULLIF(j.state,''), c.state) AS state,
                    COALESCE(NULLIF(j.zip,''), c.zip) AS zip,
                    comp.name AS company_name
             FROM jobs j
             JOIN customers c ON c.customer_id = j.customer_id
             LEFT JOIN companies comp ON comp.company_id = j.company_id
             WHERE j.scheduled_date = :date
               AND j.status NOT IN ('completed','canceled','cancelled','estimate')
             ORDER BY j.scheduled_time ASC",
            [':date' => $date]
        );
    }

    public function getStats(string $period = 'daily'): array
    {
        $today = date('Y-m-d');

        switch ($period) {
            case 'weekly':
                [$startDate, $endDate] = week_bounds();
                break;
            case 'monthly':
                $startDate = date('Y-m-01');
                $endDate   = date('Y-m-t');
                break;
            case 'daily':
            default:
                $startDate = $today;
                $endDate   = $today;
                break;
        }

        $periodJobs = $this->db->rawFirst(
            "SELECT COUNT(*) AS cnt FROM jobs
             WHERE scheduled_date BETWEEN :start AND :end",
            [':start' => $startDate, ':end' => $endDate]
        )['cnt'] ?? 0;

        // All-time: sum job_total for pending_payment — intentionally ignores period filter
        $pendingPayment = $this->db->rawFirst(
            "SELECT COALESCE(SUM(job_total), 0) AS total
             FROM jobs
             WHERE status = 'pending_payment'"
        );

        $periodProfit = $this->db->rawFirst(
            "SELECT COALESCE(SUM(jf.net_profit), 0) AS total
             FROM job_financials jf
             JOIN jobs j ON j.job_id = jf.job_id
             WHERE DATE(j.completed_at) BETWEEN :start AND :end",
            [':start' => $startDate, ':end' => $endDate]
        );

        $statusCountsData = $this->db->raw(
            "SELECT status, COUNT(*) AS cnt
             FROM jobs
             WHERE scheduled_date BETWEEN :start AND :end
             GROUP BY status",
            [':start' => $startDate, ':end' => $endDate]
        );

        $byStatus = [];
        foreach ($statusCountsData as $row) {
            $byStatus[$row['status']] = (int) $row['cnt'];
        }
        $byStatus['progress'] = ($byStatus['progress'] ?? 0)
            + ($byStatus['deposit'] ?? 0)
            + ($byStatus['in_progress'] ?? 0);
        unset($byStatus['deposit']);

        return [
            'daily_jobs'          => (int) $periodJobs,
            'pending_payment'     => (float) ($pendingPayment['total'] ?? 0),
            'period_net_profit'   => (float) ($periodProfit['total'] ?? 0),
            'today_leads'         => $this->getLeadsForDate($today),
            'status_counts'       => $byStatus,
            'follow_up_estimates' => $this->getFollowUpEstimates(),
            'count_completed'     => (int) ($byStatus['completed'] ?? 0),
            'count_canceled'      => (int) ($byStatus['canceled'] ?? 0) + (int) ($byStatus['cancelled'] ?? 0),
            'past_due_jobs'       => $this->getPastDueJobs(),
        ];
    }

    public function getPastDueJobs(): array
    {
        return $this->db->raw(
            "SELECT j.job_id, j.scheduled_date, j.scheduled_time, j.job_type, j.status, j.job_total,
                    c.full_name AS customer_name,
                    c.phone AS customer_phone,
                    COALESCE(NULLIF(j.address,''), c.address_line1) AS address_line1,
                    COALESCE(NULLIF(j.city,''), c.city) AS city,
                    COALESCE(NULLIF(j.state,''), c.state) AS state,
                    comp.name AS company_name
             FROM jobs j
             JOIN customers c ON c.customer_id = j.customer_id
             LEFT JOIN companies comp ON comp.company_id = j.company_id
             WHERE j.status IN ('progress', 'scheduled')
               AND (
                 j.scheduled_date < CURDATE()
                 OR (j.scheduled_date = CURDATE() AND j.scheduled_time IS NOT NULL AND j.scheduled_time < CURTIME())
               )
             ORDER BY j.scheduled_date ASC, j.scheduled_time ASC"
        );
    }

    public function getFollowUpEstimates(): array
    {
        return $this->db->raw(
            "SELECT j.job_id, j.scheduled_date,
                    c.full_name AS customer_name,
                    c.phone     AS customer_phone,
                    jm_amt.meta_value  AS estimate_amount,
                    jm_fup.meta_value  AS follow_up_date
             FROM jobs j
             JOIN customers c ON c.customer_id = j.customer_id
             LEFT JOIN job_meta jm_amt ON jm_amt.job_id = j.job_id AND jm_amt.meta_key = 'estimate_amount'
             LEFT JOIN job_meta jm_fup ON jm_fup.job_id = j.job_id AND jm_fup.meta_key = 'follow_up_date'
             WHERE j.status = 'estimate'
               AND jm_fup.meta_value IS NOT NULL
               AND jm_fup.meta_value <= CURDATE()
             ORDER BY jm_fup.meta_value ASC"
        );
    }
}
