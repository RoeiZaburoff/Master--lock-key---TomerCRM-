<?php
declare(strict_types=1);

namespace App\Modules\Reports;

require_once BASE_PATH . '/libs/fpdf/fpdf.php';

class ReportPdf extends \FPDF
{
    public function generate(array $rows, array $totals, array $filters): void
    {
        $this->SetMargins(10, 12, 10);
        $this->AddPage('L');
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(30, 30, 30);
        $this->Cell(0, 10, APP_NAME . ' — Financial Report', 0, 1, 'C');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 100, 100);
        $range = '';
        if (!empty($filters['date_from'])) {
            $range .= 'From: ' . $filters['date_from'] . '  ';
        }
        if (!empty($filters['date_to'])) {
            $range .= 'To: ' . $filters['date_to'];
        }
        if ($range === '') {
            $range = 'All Time';
        }
        $this->Cell(0, 6, $range, 0, 1, 'C');
        $this->Ln(3);

        $statusFilter  = $filters['status_filter'] ?? 'completed';
        $showDeposit   = $statusFilter === 'deposit' || $statusFilter === 'all';
        $mixedStatuses = $statusFilter === 'all';

        $cols = ['Lead #' => 14, 'Customer' => 32, 'Company' => 24, 'Date' => 18, 'Lead Source' => 22, 'Contractor' => 22, 'Total' => 18];
        if ($showDeposit) {
            $cols['Deposit'] = 18;
        }
        if (!$showDeposit || $mixedStatuses) {
            $cols['Parts by Tech']  = 18;
            $cols['Parts by Co.']   = 18;
            $cols['Lead Fee']       = 16;
            $cols['Contr. Share']   = 18;
            $cols['Net Profit']     = 20;
        }
        if ($mixedStatuses) {
            $cols = array_merge(['Lead #' => 14, 'Status' => 18], array_slice($cols, 1));
        }

        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(30, 58, 95);
        $this->SetTextColor(255, 255, 255);
        foreach ($cols as $label => $w) {
            $this->Cell($w, 7, $label, 0, 0, 'C', true);
        }
        $this->Ln();

        $this->SetFont('Arial', '', 8);
        $fill = false;
        foreach ($rows as $row) {
            $isDeposit = $row['status'] === 'deposit';
            $dateVal   = !empty($row['completed_at']) ? $row['completed_at'] : ($row['scheduled_date'] ?? '');
            $this->SetFillColor($fill ? 240 : 255, $fill ? 240 : 255, $fill ? 240 : 255);
            $this->SetTextColor(40, 40, 40);
            $this->Cell(14, 6, '#' . $row['job_id'], 0, 0, 'C', true);
            if ($mixedStatuses) {
                $this->Cell(18, 6, $isDeposit ? 'Deposit' : 'Completed', 0, 0, 'C', true);
            }
            $this->Cell(32, 6, $this->truncate($row['customer_name'], 20), 0, 0, 'L', true);
            $this->Cell(24, 6, $this->truncate($row['company_name'] ?? '—', 14), 0, 0, 'L', true);
            $this->Cell(18, 6, $dateVal ? date('m/d/Y', strtotime($dateVal)) : '—', 0, 0, 'C', true);
            $this->Cell(22, 6, $this->truncate($row['lead_source_name'], 13), 0, 0, 'L', true);
            $this->Cell(22, 6, $this->truncate($row['contractor_name'], 13), 0, 0, 'L', true);
            $this->Cell(18, 6, '$' . number_format((float) $row['job_total'], 2), 0, 0, 'R', true);
            if ($showDeposit) {
                $dep = (float)($row['deposit_amount'] ?? 0);
                $this->Cell(18, 6, $dep > 0 ? '$' . number_format($dep, 2) : '—', 0, 0, 'R', true);
            }
            if (!$showDeposit || $mixedStatuses) {
                $this->Cell(18, 6, !$isDeposit && (float)$row['parts_by_tech']    > 0 ? '$' . number_format((float)$row['parts_by_tech'], 2)    : '—', 0, 0, 'R', true);
                $this->Cell(18, 6, !$isDeposit && (float)$row['parts_by_company'] > 0 ? '$' . number_format((float)$row['parts_by_company'], 2) : '—', 0, 0, 'R', true);
                $this->Cell(16, 6, !$isDeposit && (float)$row['lead_fee']         > 0 ? '$' . number_format((float)$row['lead_fee'], 2)         : '—', 0, 0, 'R', true);
                $this->Cell(18, 6, !$isDeposit ? '$' . number_format((float)$row['contractor_share'], 2) : '—', 0, 0, 'R', true);
                $netProfit = (float) $row['net_profit'];
                $this->SetTextColor(!$isDeposit && $netProfit < 0 ? 180 : 40, !$isDeposit && $netProfit < 0 ? 0 : 40, 40);
                $this->Cell(20, 6, !$isDeposit ? '$' . number_format($netProfit, 2) : '—', 0, 1, 'R', true);
                $this->SetTextColor(40, 40, 40);
            } else {
                $this->Ln();
            }
            $fill = !$fill;
        }

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(30, 58, 95);
        $this->SetTextColor(255, 255, 255);
        $labelW = array_sum(array_slice(array_values($cols), 0, $mixedStatuses ? 7 : 6));
        $this->Cell($labelW, 8, 'TOTALS', 0, 0, 'R', true);
        $this->Cell(18, 8, '$' . number_format($totals['job_total'], 2), 0, 0, 'R', true);
        if ($showDeposit) {
            $this->Cell(18, 8, '$' . number_format($totals['deposit_amount'], 2), 0, 0, 'R', true);
        }
        if (!$showDeposit || $mixedStatuses) {
            $this->Cell(18, 8, '$' . number_format($totals['parts_by_tech'], 2), 0, 0, 'R', true);
            $this->Cell(18, 8, '$' . number_format($totals['parts_by_company'], 2), 0, 0, 'R', true);
            $this->Cell(16, 8, '$' . number_format($totals['lead_fee'], 2), 0, 0, 'R', true);
            $this->Cell(18, 8, '$' . number_format($totals['contractor_share'], 2), 0, 0, 'R', true);
            $this->Cell(20, 8, '$' . number_format($totals['net_profit'], 2), 0, 1, 'R', true);
        } else {
            $this->Ln();
        }

        $this->Output('I', 'Financial-Report-' . date('Ymd') . '.pdf');
    }

    private function truncate(string $str, int $max): string
    {
        return mb_strlen($str) > $max ? mb_substr($str, 0, $max - 1) . '.' : $str;
    }
}
