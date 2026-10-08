<?php
declare(strict_types=1);

namespace App\Modules\Leads;

require_once BASE_PATH . '/libs/fpdf/fpdf.php';

class EstimatePdf extends \FPDF
{
    private const DARK     = [30,  58,  95];
    private const MID      = [80,  80,  80];
    private const LIGHT    = [100, 100, 100];
    private const ROW_A    = [246, 249, 252];
    private const ROW_B    = [255, 255, 255];

    public function generate(array $estimate): void
    {
        $this->SetMargins(15, 14, 15);
        $this->AddPage();
        $this->renderHeader($estimate);
        $this->renderBillTo($estimate);
        $this->renderDetails($estimate);
        $this->renderTotal($estimate);

        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        $this->Output('D', 'Estimate-' . $estimate['job_id'] . '.pdf');
    }

    private function renderHeader(array $estimate): void
    {
        $name = !empty($estimate['company_name']) ? $estimate['company_name'] : APP_NAME;

        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(...self::DARK);
        $this->Cell(95, 8, $this->s($name), 0, 0, 'L');

        $this->SetFont('Arial', 'B', 22);
        $this->Cell(85, 8, 'ESTIMATE', 0, 1, 'R');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(...self::MID);
        $this->Cell(95, 5, '', 0, 0);
        $this->Cell(85, 5, '#' . $estimate['job_id'] . '   Date: ' . date('F j, Y'), 0, 1, 'R');

        $this->Ln(4);
        $this->SetDrawColor(220, 226, 236);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(6);
    }

    private function renderBillTo(array $estimate): void
    {
        $addrParts = array_filter([
            $estimate['customer_address_line1'] ?? '',
            $estimate['customer_city']          ?? '',
            $estimate['customer_state']         ?? '',
            $estimate['customer_zip']           ?? '',
        ]);
        $addr = implode(', ', $addrParts);

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(...self::LIGHT);
        $this->Cell(0, 5, 'PREPARED FOR', 0, 1, 'L');

        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(...self::DARK);
        $this->Cell(0, 6, $this->s($estimate['customer_name']), 0, 1, 'L');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(60, 60, 60);
        if ($addr !== '') {
            $this->Cell(0, 5, $this->s($addr), 0, 1, 'L');
        }
        if (!empty($estimate['customer_phone'])) {
            $this->Cell(0, 5, $this->s(format_phone($estimate['customer_phone'])), 0, 1, 'L');
        }

        $this->Ln(8);
    }

    private function renderDetails(array $estimate): void
    {
        $meta       = $estimate['meta'] ?? [];
        $dimensions = trim((string) ($meta['estimate_dimensions'] ?? ''));
        $notes      = trim((string) ($meta['estimate_notes']      ?? ''));
        $followUp   = trim((string) ($meta['follow_up_date']      ?? ''));

        $this->SetFillColor(...self::DARK);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 9, 'ESTIMATE DETAILS', 0, 1, 'L', true);

        $this->SetFont('Arial', '', 10);
        $fill = false;

        $rows = [];
        if (!empty($estimate['job_type'])) {
            $rows[] = ['Service Type', $estimate['job_type']];
        }
        if ($dimensions !== '') {
            $rows[] = ['Dimensions', $dimensions];
        }
        if ($followUp !== '') {
            $rows[] = ['Follow-up Date', $followUp];
        }

        foreach ($rows as $row) {
            $this->SetFillColor(...($fill ? self::ROW_A : self::ROW_B));
            $this->SetTextColor(40, 40, 40);
            $this->Cell(55, 8, $this->s($row[0]), 0, 0, 'L', true);
            $this->Cell(125, 8, $this->s($row[1]), 0, 1, 'L', true);
            $fill = !$fill;
        }

        if (!empty($estimate['description'])) {
            $this->Ln(5);
            $this->SetFont('Arial', 'B', 9);
            $this->SetTextColor(...self::LIGHT);
            $this->Cell(0, 5, 'DESCRIPTION', 0, 1, 'L');
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(60, 60, 60);
            $this->MultiCell(0, 6, $this->s($estimate['description']), 0, 'L');
        }

        if ($notes !== '') {
            $this->Ln(5);
            $this->SetFont('Arial', 'B', 9);
            $this->SetTextColor(...self::LIGHT);
            $this->Cell(0, 5, 'NOTES', 0, 1, 'L');
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(60, 60, 60);
            $this->MultiCell(0, 6, $this->s($notes), 0, 'L');
        }

        $this->Ln(6);
    }

    private function renderTotal(array $estimate): void
    {
        $meta   = $estimate['meta'] ?? [];
        $amount = (float) ($meta['estimate_amount'] ?? 0);

        $this->SetDrawColor(220, 226, 236);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(4);

        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(...self::DARK);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(150, 10, 'ESTIMATED TOTAL', 0, 0, 'L', true);
        $this->Cell(30, 10, '$' . number_format($amount, 2), 0, 1, 'R', true);

        $this->Ln(6);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(...self::LIGHT);
        $this->Cell(0, 5, 'This is an estimate only. Final pricing may vary based on actual scope of work.', 0, 1, 'C');
    }

    private function s(string $str): string
    {
        return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
    }
}
