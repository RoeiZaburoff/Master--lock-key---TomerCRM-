<?php
declare(strict_types=1);

namespace App\Services;

class ProfitCalculator
{
    public function calculate(
        float $jobTotal,
        array|null $leadSource,
        array|null $contractor,
        float $materialTech = 0.0,
        float $materialProvider = 0.0,
        float $ccFeePercentage = 0.0,
        string $paymentMethod = 'cash',
        bool $isSpecialClosing = false,
        ?float $providerRetainedAmount = null
    ): array {
        $totalMaterials = $materialTech + $materialProvider;

        if ($isSpecialClosing && $providerRetainedAmount !== null) {
            $leadFee       = round($jobTotal - $providerRetainedAmount, 2);
            $providerFinal = round($providerRetainedAmount - $totalMaterials, 2);

            return [
                'job_total'        => round($jobTotal, 2),
                'material_cost'    => round($totalMaterials, 2),
                'lead_fee'         => $leadFee,
                'contractor_share' => round($materialTech, 2),
                'gross_profit'     => round($jobTotal - $totalMaterials, 2),
                'net_profit'       => $providerFinal,
                'cc_fee'           => 0.00,
            ];
        }

        $netProfit = $jobTotal - $totalMaterials;

        $leadFee = $leadSource !== null
            ? $this->resolveLeadFee($leadSource['fee_type'], (float) $leadSource['fee_value'], $netProfit)
            : 0.00;

        $commission = $contractor !== null
            ? $this->resolveContractorShare($contractor['split_type'], (float) $contractor['split_value'], $netProfit)
            : 0.00;

        $ccFee = $paymentMethod === 'credit_card' && $ccFeePercentage > 0.0
            ? round($jobTotal * ($ccFeePercentage / 100), 2)
            : 0.00;

        $techFinal     = round($commission + $materialTech, 2);
        $providerFinal = round(($netProfit - $leadFee - $commission) + $materialProvider - $ccFee, 2);

        return [
            'job_total'        => round($jobTotal, 2),
            'material_cost'    => round($totalMaterials, 2),
            'lead_fee'         => round($leadFee, 2),
            'contractor_share' => $techFinal,
            'gross_profit'     => round($netProfit, 2),
            'net_profit'       => $providerFinal,
            'cc_fee'           => $ccFee,
        ];
    }

    public function resolveLeadFee(string $feeType, float $feeValue, float $base): float
    {
        return $feeType === 'percent' ? $base * $feeValue : $feeValue;
    }

    public function resolveContractorShare(string $splitType, float $splitValue, float $base): float
    {
        return $splitType === 'percent' ? $base * $splitValue : $splitValue;
    }
}
