<?php
declare(strict_types=1);

namespace App\Modules\Jobs;

use App\Core\QueryBuilder;
use App\Core\Validator;
use App\Modules\Inventory\InventoryRepository;
use App\Services\ProfitCalculator;

class JobService
{
    private JobRepository       $repo;
    private QueryBuilder        $db;
    private InventoryRepository $inventoryRepo;

    public function __construct()
    {
        $this->repo          = new JobRepository();
        $this->db            = new QueryBuilder();
        $this->inventoryRepo = new InventoryRepository();
    }

    public function getAll(array $filters = []): array
    {
        return $this->repo->findAll($filters);
    }

    public function getById(int $id): array|null
    {
        return $this->repo->findById($id);
    }

    public function getDetail(int $id): array|null
    {
        $job = $this->repo->findById($id);
        if ($job === null) {
            return null;
        }
        $job['materials']  = $this->repo->getMaterials($id);
        $job['financials'] = $this->repo->getFinancials($id);
        return $job;
    }

    public function create(array $data): array
    {
        $v = new Validator();
        if (!$v->validate($data, $this->rules())) {
            return ['success' => false, 'errors' => $v->errors()];
        }
        $id = $this->repo->create($this->sanitize($data));
        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        $v = new Validator();
        if (!$v->validate($data, $this->rules())) {
            return ['success' => false, 'errors' => $v->errors()];
        }
        $this->repo->update($id, $this->sanitize($data));
        return ['success' => true];
    }

    public function complete(int $id, array $materialsInput): array
    {
        $job = $this->repo->findById($id);
        if ($job === null) {
            return ['success' => false, 'errors' => ['job' => ['Job not found.']]];
        }

        // The stock bucket to deduct from / refund to is the contractor assigned to the job
        $contractorId = !empty($job['contractor_id']) ? (int) $job['contractor_id'] : null;

        $paymentMethod = in_array(
            $materialsInput['payment_method'] ?? '',
            ['cash', 'credit_card', 'check', 'zelle', 'cash_app'],
            true
        ) ? $materialsInput['payment_method'] : 'cash';

        $isSpecialClosing       = !empty($materialsInput['is_special_closing']);
        $providerRetainedAmount = null;
        if ($isSpecialClosing && isset($materialsInput['provider_retained_amount'])) {
            $parsed = (float) $materialsInput['provider_retained_amount'];
            if ($parsed >= 0.0) {
                $providerRetainedAmount = $parsed;
            }
        }

        $this->db->beginTransaction();
        try {
            // 1. Refund existing materials' stock
            foreach ($this->repo->getMaterials($id) as $existing) {
                if (($existing['ownership'] ?? '') === 'company' && !empty($existing['inventory_id']) && !empty($existing['location_id'])) {
                    $this->inventoryRepo->incrementStock(
                        (int) $existing['inventory_id'],
                        (float) $existing['qty'],
                        (int) ($existing['location_id'] ?? 1)
                    );
                }
            }

            // 2. Delete existing materials
            $this->repo->deleteMaterials($id);

            // 3. Insert new materials & deduct stock from the explicit location
            $materialTech     = 0.0;
            $materialProvider = 0.0;

            foreach ($materialsInput as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $invId      = !empty($row['inventory_id']) ? (int) $row['inventory_id'] : null;
                $locationId = $invId !== null ? (int) ($row['location_id'] ?? 1) : null;
                $desc       = trim($row['description'] ?? '');
                if ($invId === null && $desc === '') {
                    continue;
                }

                $qty       = max(0.01, (float) ($row['qty']       ?? 1));
                $unitCost  = max(0.0,  (float) ($row['unit_cost'] ?? 0));
                $lineTotal = round($qty * $unitCost, 2);
                $ownership = ($row['ownership'] ?? '') === 'company' ? 'company' : 'technician';

                $this->repo->insertMaterial([
                    'job_id'       => $id,
                    'inventory_id' => $invId,
                    'location_id'  => $locationId,
                    'ownership'    => $ownership,
                    'description'  => $desc,
                    'qty'          => $qty,
                    'unit_cost'    => $unitCost,
                    'line_total'   => $lineTotal,
                ]);

                if ($ownership === 'company') {
                    $materialProvider += $lineTotal;
                    if ($invId !== null) {
                        $this->inventoryRepo->decrementStock($invId, $qty, $locationId);
                    }
                } else {
                    $materialTech += $lineTotal;
                }
            }

            // Resolve lead source and contractor for profit calculation
            $leadSource = null;
            $contractor = null;
            if (!empty($job['lead_source_id'])) {
                $leadSource = $this->db->table('lead_sources')
                    ->where('lead_source_id', $job['lead_source_id'])->first();
            }
            if (!empty($job['contractor_id'])) {
                $contractor = $this->db->table('contractors')
                    ->where('contractor_id', $job['contractor_id'])->first();
            }

            $ccFeeRow        = $this->db->table('system_meta')->where('meta_key', 'cc_fee_percentage')->first();
            $ccFeePercentage = $ccFeeRow !== null ? (float) $ccFeeRow['meta_value'] : 0.0;

            $calc = new ProfitCalculator();
            $fin  = $calc->calculate(
                (float) $job['job_total'],
                $leadSource,
                $contractor,
                $materialTech,
                $materialProvider,
                $ccFeePercentage,
                $paymentMethod,
                $isSpecialClosing,
                $providerRetainedAmount
            );
            $fin['job_id']            = $id;
            $fin['material_tech']     = $materialTech;
            $fin['material_provider'] = $materialProvider;
            $this->repo->saveFinancials($fin);

            $completedAt = $this->normalizeDatetime($materialsInput['completed_at'] ?? '');
            if ($completedAt === '') {
                $completedAt = !empty($job['created_at']) ? $job['created_at'] : date('Y-m-d H:i:s');
            }

            $this->repo->update($id, [
                'status'                   => 'completed',
                'completed_at'             => $completedAt,
                'payment_method'           => $paymentMethod,
                'is_special_closing'       => $isSpecialClosing ? 1 : 0,
                'provider_retained_amount' => $providerRetainedAmount,
            ]);

            $this->db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'errors' => ['job' => ['Transaction Failed: ' . $e->getMessage()]]];
        }
    }

    public function getFormData(): array
    {
        return [
            'customers'    => $this->db->table('customers')->orderBy('full_name')->get(),
            'lead_sources' => $this->db->table('lead_sources')->where('is_active', 1)->orderBy('name')->get(),
            'contractors'  => $this->db->table('contractors')->where('is_active', 1)->orderBy('full_name')->get(),
        ];
    }

    public function getMaterialsCatalog(?int $contractorId = null): array
    {
        if ($contractorId === null) {
            return $this->db->raw(
                "SELECT i.*, m.name AS manufacturer_name,
                        COALESCE(s.quantity, 0) AS stock_qty
                 FROM inventory i
                 LEFT JOIN manufacturers m ON m.manufacturer_id = i.manufacturer_id
                 LEFT JOIN inventory_stocks s ON s.inventory_id = i.inventory_id AND s.contractor_id IS NULL
                 ORDER BY i.name ASC"
            );
        }
        return $this->db->raw(
            "SELECT i.*, m.name AS manufacturer_name,
                    COALESCE(s.quantity, 0) AS stock_qty
             FROM inventory i
             LEFT JOIN manufacturers m ON m.manufacturer_id = i.manufacturer_id
             LEFT JOIN inventory_stocks s ON s.inventory_id = i.inventory_id AND s.contractor_id = :cid
             ORDER BY i.name ASC",
            [':cid' => $contractorId]
        );
    }

    private function normalizeDatetime(string $raw): string
    {
        $raw = trim(str_replace('T', ' ', $raw));
        if ($raw !== '' && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $raw)) {
            return strlen($raw) === 16 ? $raw . ':00' : $raw;
        }
        return '';
    }

    private function rules(): array
    {
        return [
            'customer_id' => 'required',
            'job_type'    => 'required|max:100',
            'job_total'   => 'required|numeric|min:0',
        ];
    }

    private function sanitize(array $data): array
    {
        return [
            'customer_id'    => (int) $data['customer_id'],
            'lead_source_id' => !empty($data['lead_source_id']) ? (int) $data['lead_source_id'] : null,
            'contractor_id'  => !empty($data['contractor_id'])  ? (int) $data['contractor_id']  : null,
            'job_type'       => trim($data['job_type']),
            'description'    => trim($data['description'] ?? ''),
            'scheduled_date' => !empty($data['scheduled_date']) ? $data['scheduled_date'] : null,
            'scheduled_time' => !empty($data['scheduled_time']) ? $data['scheduled_time'] : null,
            'status'         => $data['status'] ?? 'pending',
            'job_total'      => (float) $data['job_total'],
            'payment_status' => $data['payment_status'] ?? 'unpaid',
            'amount_paid'    => (float) ($data['amount_paid'] ?? 0),
        ];
    }
}
