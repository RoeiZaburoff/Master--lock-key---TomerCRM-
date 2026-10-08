<?php
declare(strict_types=1);

namespace App\Modules\Invoices;

require_once BASE_PATH . '/libs/fpdf/fpdf.php';

class InvoicePdf extends \FPDF
{
    private const LOGO_W   = 42;
    private const LOGO_H   = 18;
    private const LOGO_X   = 15;
    private const LOGO_Y   = 14;
    private const TEXT_X   = 62;
    private const COL_L    = 150;
    private const COL_R    = 30;
    private const DARK     = [30,  58,  95];
    private const MID      = [80,  80,  80];
    private const LIGHT    = [100, 100, 100];
    private const ROW_FILL = [246, 249, 252];

    public function generate(array $invoice): void
    {
        $this->SetMargins(15, 14, 15);
        $this->AddPage();

        $name    = !empty($invoice['company_name'])    ? $invoice['company_name']    : APP_NAME;
        $addr    = $invoice['company_address'] ?? '';
        $phone   = $invoice['company_phone']   ?? '';
        $email   = $invoice['company_email']   ?? '';
        $logo    = !empty($invoice['company_logo'])
            ? STORAGE_PATH . '/logos/' . $invoice['company_logo']
            : null;

        $this->renderHeader($name, $addr, $phone, $email, $logo, $invoice);
        $this->renderBillTo($invoice);
        $this->renderItemsTable($invoice);
        $this->renderTotals($invoice);
        if (!empty($invoice['payment_method'])) {
            $this->renderPayment($invoice);
        }
        if (!empty($invoice['notes'])) {
            $this->renderNotes($invoice['notes']);
        }

        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        $this->Output('I', 'Invoice-' . $invoice['invoice_number'] . '.pdf');
    }

    private function renderHeader(string $name, string $addr, string $phone, string $email, ?string $logo, array $invoice): void
    {
        $tmpLogo = null;
        if ($logo !== null && file_exists($logo)) {
            $ext = strtolower(pathinfo($logo, PATHINFO_EXTENSION));
            if ($ext === 'webp') {
                $img = @imagecreatefromwebp($logo);
                if ($img !== false) {
                    $tmpLogo = tempnam(sys_get_temp_dir(), 'inv_logo_') . '.png';
                    imagesavealpha($img, true);
                    imagepng($img, $tmpLogo, 9);
                    imagedestroy($img);
                    $logo = $tmpLogo;
                    $ext  = 'png';
                } else {
                    $logo = null;
                }
            }
            if ($logo !== null && in_array($ext, ['jpg','jpeg','png','gif'], true)) {
                $size = @getimagesize($logo);
                if ($size && $size[0] > 0 && $size[1] > 0) {
                    $pxW = $size[0];
                    $pxH = $size[1];
                    $ratio = $pxW / $pxH;
                    $boxW = self::LOGO_W;
                    $boxH = self::LOGO_H;
                    if ($ratio >= ($boxW / $boxH)) {
                        $drawW = $boxW;
                        $drawH = $boxW / $ratio;
                    } else {
                        $drawH = $boxH;
                        $drawW = $boxH * $ratio;
                    }
                    $offsetX = self::LOGO_X + ($boxW - $drawW) / 2;
                    $offsetY = self::LOGO_Y + ($boxH - $drawH) / 2;
                    $this->Image($logo, $offsetX, $offsetY, $drawW, $drawH);
                } else {
                    $this->Image($logo, self::LOGO_X, self::LOGO_Y, self::LOGO_W, self::LOGO_H);
                }
            }
        }
        if ($tmpLogo !== null && file_exists($tmpLogo)) {
            unlink($tmpLogo);
        }

        $this->SetXY(self::TEXT_X, self::LOGO_Y);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(...self::DARK);
        $this->Cell(0, 8, $this->s($name), 0, 1, 'L');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(...self::LIGHT);
        $details = array_filter([$addr, $phone, $email]);
        foreach ($details as $line) {
            $this->SetX(self::TEXT_X);
            $this->Cell(0, 5, $this->s($line), 0, 1, 'L');
        }

        $headerBottom = max($this->GetY(), self::LOGO_Y + self::LOGO_H + 2);

        $this->SetFont('Arial', 'B', 22);
        $this->SetTextColor(...self::DARK);
        $this->SetXY(15, self::LOGO_Y);
        $this->Cell(0, 8, 'INVOICE', 0, 0, 'R');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(...self::MID);
        $this->SetXY(15, self::LOGO_Y + 10);
        $this->Cell(0, 5, $invoice['invoice_number'], 0, 0, 'R');

        $this->SetY($headerBottom + 4);
        $this->SetDrawColor(220, 226, 236);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(6);
    }

    private function renderBillTo(array $invoice): void
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(...self::LIGHT);
        $this->Cell(95, 5, 'BILL TO', 0, 0, 'L');
        $this->Cell(95, 5, 'INVOICE DETAILS', 0, 1, 'R');

        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(...self::DARK);
        $this->Cell(95, 6, $this->s($invoice['customer_name']), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(...self::MID);
        $this->Cell(95, 6, 'Invoice:  ' . $invoice['invoice_number'], 0, 1, 'R');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(60, 60, 60);
        $this->Cell(95, 5, $this->s($invoice['address_line1'] ?? ''), 0, 0, 'L');
        $this->SetTextColor(...self::MID);
        $this->Cell(95, 5, 'Date:       ' . $invoice['issued_date'], 0, 1, 'R');

        $city = trim(($invoice['city'] ?? '') . ', ' . ($invoice['state'] ?? '') . ' ' . ($invoice['zip'] ?? ''), ', ');
        $this->SetTextColor(60, 60, 60);
        $this->Cell(95, 5, $this->s($city), 0, 0, 'L');
        $due = !empty($invoice['due_date']) ? $invoice['due_date'] : 'Upon Receipt';
        $this->SetTextColor(...self::MID);
        $this->Cell(95, 5, 'Due:         ' . $due, 0, 1, 'R');

        if (!empty($invoice['customer_phone'])) {
            $this->SetTextColor(60, 60, 60);
            $this->Cell(95, 5, $this->s(format_phone($invoice['customer_phone'])), 0, 1, 'L');
        }

        $this->Ln(8);
    }

    private function renderItemsTable(array $invoice): void
    {
        $this->SetFillColor(...self::DARK);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(self::COL_L, 9, 'Description', 0, 0, 'L', true);
        $this->Cell(self::COL_R, 9, 'Amount', 0, 1, 'R', true);

        $items = $invoice['items'] ?? [];

        if (!empty($items)) {
            $fill = false;
            foreach ($items as $item) {
                $this->SetFillColor(...($fill ? self::ROW_FILL : [255, 255, 255]));
                $this->SetTextColor(40, 40, 40);
                $this->SetFont('Arial', '', 10);
                $desc = mb_strlen($item['description']) > 88
                    ? mb_substr($item['description'], 0, 85) . '...'
                    : $item['description'];
                $this->Cell(self::COL_L, 9, $this->s($desc), 0, 0, 'L', true);
                $this->Cell(self::COL_R, 9, '$' . number_format((float) $item['amount'], 2), 0, 1, 'R', true);
                $fill = !$fill;
            }
        } else {
            $this->SetFillColor(...self::ROW_FILL);
            $this->SetTextColor(40, 40, 40);
            $this->SetFont('Arial', '', 10);
            $desc = !empty($invoice['job_description']) ? $invoice['job_description'] : '—';
            $this->Cell(self::COL_L, 9, $this->s($desc), 0, 0, 'L', true);
            $this->Cell(self::COL_R, 9, '$' . number_format((float) $invoice['subtotal'], 2), 0, 1, 'R', true);
        }

        $this->Ln(4);
    }

    private function renderTotals(array $invoice): void
    {
        $labelW = 130;
        $valW   = 50;

        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(60, 60, 60);
        $this->Cell($labelW, 7, '', 0, 0);
        $this->Cell($valW, 7, 'Subtotal: $' . number_format((float) $invoice['subtotal'], 2), 0, 1, 'R');

        if ((float) $invoice['tax_amount'] > 0) {
            $taxPct = round((float) $invoice['tax_rate'] * 100, 2);
            $this->Cell($labelW, 7, '', 0, 0);
            $this->Cell($valW, 7, "Tax ({$taxPct}%): \$" . number_format((float) $invoice['tax_amount'], 2), 0, 1, 'R');
        }

        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(...self::DARK);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($labelW, 10, '', 0, 0);
        $this->Cell($valW, 10, 'TOTAL: $' . number_format((float) $invoice['total'], 2), 0, 1, 'R', true);

        $deposit = (float) ($invoice['deposit_amount'] ?? 0);
        if ($deposit > 0) {
            $balance = max(0.0, (float) $invoice['total'] - $deposit);

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(22, 163, 74); // green
            $this->Cell($labelW, 7, '', 0, 0);
            $this->Cell($valW, 7, 'Deposit Paid: -$' . number_format($deposit, 2), 0, 1, 'R');

            $this->SetFont('Arial', 'B', 11);
            $this->SetFillColor(22, 163, 74);
            $this->SetTextColor(255, 255, 255);
            $this->Cell($labelW, 10, '', 0, 0);
            $this->Cell($valW, 10, 'BALANCE DUE: $' . number_format($balance, 2), 0, 1, 'R', true);
        }
    }

    private function renderPayment(array $invoice): void
    {
        $methodKey = $invoice['payment_method'] ?? '';
        $method = match($methodKey) {
            'credit_card' => 'Credit Card',
            'check'       => 'Check',
            'cash'        => 'Cash',
            'zelle'       => 'Zelle',
            'cash_app'    => 'Cash App',
            default       => $methodKey,
        };

        $line = 'Payment Method: ' . $method;
        if (!empty($invoice['payment_reference'])) {
            if ($methodKey === 'check') {
                $refLabel = 'Check #';
            } elseif ($methodKey === 'credit_card') {
                $refLabel = 'Card ending ';
            } else {
                $refLabel = 'Ref: ';
            }
            $line .= '   |   ' . $refLabel . $invoice['payment_reference'];
        }

        $this->Ln(4);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(...self::MID);
        $this->Cell(0, 6, $this->s($line), 0, 1, 'R');
    }

    private function renderNotes(string $notes): void
    {
        $this->Ln(8);
        $this->SetDrawColor(220, 226, 236);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(6);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(...self::LIGHT);
        $this->Cell(0, 5, 'NOTES', 0, 1);
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(60, 60, 60);
        $this->MultiCell(0, 5, $this->s($notes), 0, 'L');
    }

    private function s(string $str): string
    {
        return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
    }
}
