<?php
declare(strict_types=1);

namespace App\Modules\Leads;

use App\Core\QueryBuilder;
use App\Core\Validator;
use App\Modules\Inventory\InventoryRepository;
use App\Services\ProfitCalculator;

class LeadService
{
    private LeadRepository      $repo;
    private QueryBuilder        $db;
    private InventoryRepository $inventoryRepo;

    public function __construct()
    {
        $this->repo          = new LeadRepository();
        $this->db            = new QueryBuilder();
        $this->inventoryRepo = new InventoryRepository();
    }

    public function getAll(array $filters = []): array
    {
        return $this->repo->findAll($filters);
    }

    public function getById(int $id): array|null
    {
        $lead = $this->repo->findById($id);
        if ($lead === null) {
            return null;
        }
        $lead = $this->hydrateMaterialFields($lead);
        $lead['meta'] = $this->repo->getMeta($id);
        return $lead;
    }

    public function getDetail(int $id): array|null
    {
        $lead = $this->repo->findById($id);
        if ($lead === null) {
            return null;
        }
        $lead = $this->hydrateMaterialFields($lead);
        $lead['financials'] = $this->repo->getFinancials($id);
        $lead['meta']       = $this->repo->getMeta($id);
        return $lead;
    }

    private function hydrateMaterialFields(array $lead): array
    {
        $hasSplit = array_key_exists('material_tech', $lead) && array_key_exists('material_provider', $lead);
        $bothZero = $hasSplit && (float) $lead['material_tech'] === 0.0 && (float) $lead['material_provider'] === 0.0;

        if (!$hasSplit || $bothZero) {
            $legacyCost = (float) ($lead['material_cost'] ?? 0);
            $legacyBy   = $lead['material_provided_by'] ?? 'company';
            $lead['material_tech']     = ($legacyBy === 'contractor' || $legacyBy === 'technician') ? $legacyCost : 0.0;
            $lead['material_provider'] = $legacyBy === 'company'    ? $legacyCost : 0.0;
        }

        return $lead;
    }

    private function getValidationRules(array $data): array
    {
        $rules = ['customer_id' => 'required'];
        if (($data['status'] ?? '') !== 'estimate') {
            $rules['company_id'] = 'required';
        }
        if (($data['status'] ?? '') === 'scheduled') {
            $rules['scheduled_date'] = 'required';
        }
        return $rules;
    }

    public function create(array $data): array
    {
        $v = new Validator();
        if (!$v->validate($data, $this->getValidationRules($data))) {
            return ['success' => false, 'errors' => $v->errors()];
        }

        $sanitized = $this->sanitize($data);
        $sanitized['duration_hours'] = max(1, (int) ($data['duration_hours'] ?? 1));

        $depositReceived = !empty($data['deposit_received']) ? 1 : 0;
        $depositAmt      = $depositReceived ? (float) ($data['deposit_amount'] ?? 0) : 0.00;

        $sanitized['deposit_received'] = $depositReceived;
        $sanitized['deposit_amount']   = $depositAmt;

        $createdAt = $this->normalizeDatetime($data['created_at'] ?? '');
        if ($createdAt !== '') {
            $sanitized['created_at'] = $createdAt;
        }

        $id = $this->repo->create($sanitized);
        if ($sanitized['status'] === 'scheduled') {
            $this->logScheduleEntry($id, $sanitized['scheduled_date'], $sanitized['scheduled_time']);
        }
        if ($sanitized['status'] === 'estimate') {
            $this->saveEstimateMeta($id, $data);
        }
        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        $v = new Validator();
        if (!$v->validate($data, $this->getValidationRules($data))) {
            return ['success' => false, 'errors' => $v->errors()];
        }

        $existing  = $this->repo->findById($id);
        $sanitized = $this->sanitize($data);

        if ($existing !== null) {
            $existingStatus   = $existing['status'] ?? '';
            $paymentSubmitted = isset($data['payment_status']) && $data['payment_status'] !== '';
            if (
                !$paymentSubmitted ||
                (in_array($existingStatus, ['completed', 'closed']) && $sanitized['payment_status'] === 'unpaid')
            ) {
                $sanitized['payment_status'] = $existing['payment_status'] ?? 'unpaid';
            }
        }

        $sanitized['duration_hours'] = max(1, (int) ($data['duration_hours'] ?? 1));

        $depositReceived = !empty($data['deposit_received']) ? 1 : 0;
        $depositAmt      = $depositReceived ? (float) ($data['deposit_amount'] ?? 0) : 0.00;

        $sanitized['deposit_received'] = $depositReceived;
        $sanitized['deposit_amount']   = $depositAmt;

        $createdAt = $this->normalizeDatetime($data['created_at'] ?? '');
        if ($createdAt !== '') {
            $sanitized['created_at'] = $createdAt;
        }

        $this->repo->update($id, $sanitized);
        if ($sanitized['status'] === 'scheduled') {
            $existingStatus = $existing['status'] ?? '';
            if ($existingStatus !== 'scheduled' || $sanitized['scheduled_date'] !== ($existing['scheduled_date'] ?? '')) {
                $this->logScheduleEntry($id, $sanitized['scheduled_date'], $sanitized['scheduled_time']);
            }
        }
        if ($sanitized['status'] === 'estimate') {
            $this->saveEstimateMeta($id, $data);
        }
        return ['success' => true];
    }

    private function logScheduleEntry(int $id, string $date, ?string $time): void
    {
        $meta    = $this->repo->getMeta($id);
        $log     = [];
        if (!empty($meta['sched_log'])) {
            $decoded = json_decode($meta['sched_log'], true);
            if (is_array($decoded)) {
                $log = $decoded;
            }
        }
        $log[] = [
            'date'      => $date,
            'time'      => $time,
            'logged_at' => date('Y-m-d H:i:s'),
        ];
        $this->repo->upsertMeta($id, 'sched_log', json_encode($log));
    }

    public function saveEstimateMeta(int $id, array $data): void
    {
        $keys = ['estimate_amount', 'estimate_dimensions', 'estimate_notes', 'follow_up_date'];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $val = ($data[$key] !== null && $data[$key] !== '') ? (string) $data[$key] : null;
                $this->repo->upsertMeta($id, $key, $val);
            }
        }
    }

    public function convertToJob(int $id, array $data): array
    {
        $lead = $this->repo->findById($id);
        if ($lead === null || ($lead['status'] ?? '') !== 'estimate') {
            return ['success' => false, 'errors' => ['job' => ['Estimate not found or already converted.']]];
        }

        $scheduledDate = trim($data['scheduled_date'] ?? '');
        if ($scheduledDate === '') {
            return ['success' => false, 'errors' => ['scheduled_date' => ['Scheduled date is required.']]];
        }

        $meta           = $this->repo->getMeta($id);
        $estimateAmount = (float) ($meta['estimate_amount'] ?? 0);

        $scheduledTime = trim($data['scheduled_time'] ?? '');
        $timeVal       = $scheduledTime !== '' ? $scheduledTime . ':00' : null;

        $this->repo->update($id, [
            'status'         => 'pending',
            'job_total'      => $estimateAmount,
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $timeVal,
        ]);

        return ['success' => true];
    }

    private function normalizeDatetime(string $raw): string
    {
        $raw = trim(str_replace('T', ' ', $raw));
        if ($raw !== '' && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $raw)) {
            return strlen($raw) === 16 ? $raw . ':00' : $raw;
        }
        return '';
    }

    private function calculateAndSaveFinancials(
        int $id,
        array $lead,
        float $jobTotal,
        float $materialTech,
        float $materialProvider,
        string $paymentMethod = 'cash',
        bool $isSpecialClosing = false,
        ?float $providerRetainedAmount = null
    ): void {
        $leadSource = null;
        $contractor = null;
        if (!empty($lead['lead_source_id'])) {
            $leadSource = $this->db->table('lead_sources')
                ->where('lead_source_id', $lead['lead_source_id'])->first();
        }
        if (!empty($lead['contractor_id'])) {
            $contractor = $this->db->table('contractors')
                ->where('contractor_id', $lead['contractor_id'])->first();
        }

        $ccFeeRow        = $this->db->table('system_meta')->where('meta_key', 'cc_fee_percentage')->first();
        $ccFeePercentage = $ccFeeRow !== null ? (float) $ccFeeRow['meta_value'] : 0.0;

        $existing = $this->repo->getFinancials($id);
        $policy = !empty($existing['calculation_policy']) ? json_decode($existing['calculation_policy'], true, 32, JSON_THROW_ON_ERROR) : null;
        if (is_array($policy)) {
            $leadSource = $policy['lead_source'];
            $contractor = $policy['contractor'];
            $ccFeePercentage = (float) $policy['cc_fee_percentage'];
        }
        $policy = ['lead_source' => $leadSource === null ? null : array_intersect_key($leadSource, array_flip(['fee_type','fee_value'])),
                   'contractor' => $contractor === null ? null : array_intersect_key($contractor, array_flip(['split_type','split_value'])),
                   'cc_fee_percentage' => $ccFeePercentage];
        $calc = new ProfitCalculator();
        $fin  = $calc->calculate(
            $jobTotal, $leadSource, $contractor, $materialTech, $materialProvider,
            $ccFeePercentage, $paymentMethod, $isSpecialClosing, $providerRetainedAmount
        );
        $fin['calculation_policy'] = json_encode($policy, JSON_THROW_ON_ERROR);
        $fin['job_id']            = $id;
        $fin['material_tech']     = $materialTech;
        $fin['material_provider'] = $materialProvider;
        $this->repo->saveFinancials($fin);
    }

    public function getMaterialsCatalog(): array
    {
        return $this->db->raw(
            "SELECT i.*, m.name AS manufacturer_name
             FROM inventory i
             LEFT JOIN manufacturers m ON m.manufacturer_id = i.manufacturer_id
             ORDER BY i.name ASC"
        );
    }

    public function getMaterials(int $id): array
    {
        return $this->repo->getMaterials($id);
    }

    public function complete(int $id, array $input): array
    {
        $lead = $this->repo->findById($id);
        if ($lead === null) {
            return ['success' => false, 'errors' => ['lead' => ['Lead not found.']]];
        }

        $jobTotal      = !empty($input['job_total']) ? (float) $input['job_total'] : (float) $lead['job_total'];
        $paymentMethod = $input['payment_method'] ?? null;
        $pmValid       = in_array($paymentMethod, ['cash', 'credit_card', 'check', 'zelle', 'cash_app'], true);

        $isSpecialClosing       = !empty($input['is_special_closing']);
        $providerRetainedAmount = null;
        if ($isSpecialClosing && isset($input['provider_retained_amount']) && $input['provider_retained_amount'] !== '') {
            $providerRetainedAmount = (float) $input['provider_retained_amount'];
        }

        if ($isSpecialClosing && $providerRetainedAmount === null) {
            return ['success' => false, 'errors' => ['provider_retained_amount' => ['Retained amount is required for Special Closing.']]];
        }

        $contractorId = !empty($lead['contractor_id']) ? (int) $lead['contractor_id'] : null;

        $this->db->beginTransaction();
        try {
            // 1. Refund existing materials' stock
            foreach ($this->repo->getMaterials($id) as $existing) {
                $invId = !empty($existing['inventory_id']) ? (int) $existing['inventory_id'] : null;
                if ($invId !== null && !empty($existing['location_id'])) {
                    $this->inventoryRepo->incrementStock($invId, (float) $existing['qty'], (int) $existing['location_id']);
                }
            }

            // 2. Delete existing materials
            $this->repo->deleteMaterials($id);

            // 3. Insert new materials & deduct stock
            $materialTech     = 0.0;
            $materialProvider = 0.0;

            foreach ($input['materials'] ?? [] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $invId      = !empty($row['inventory_id']) && $row['inventory_id'] !== 'null' ? (int) $row['inventory_id'] : null;
                $locationId = $invId !== null ? (int) ($row['location_id'] ?? 1) : null;

                if ($invId === null) {
                    $rawCost = (float) ($row['unit_cost'] ?? 0);
                    if ($rawCost <= 0) {
                        continue;
                    }
                    $desc = 'Manual Entry';
                    $qty  = 1.0;
                } else {
                    $desc = trim($row['description'] ?? '');
                    $qty  = max(0.01, (float) ($row['qty'] ?? 1));
                }

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

                if ($invId !== null && $locationId !== null) {
                    $this->inventoryRepo->decrementStock($invId, $qty, $locationId);
                }

                if ($ownership === 'company') {
                    $materialProvider += $lineTotal;
                } else {
                    $materialTech += $lineTotal;
                }
            }

            $this->calculateAndSaveFinancials(
                $id, $lead, $jobTotal, $materialTech, $materialProvider,
                $pmValid ? $paymentMethod : 'cash', $isSpecialClosing, $providerRetainedAmount
            );

            $completedAt = $this->normalizeDatetime($input['completed_at'] ?? '');
            if ($completedAt === '') {
                $completedAt = date('Y-m-d H:i:s');
            }

            $this->repo->update($id, [
                'job_total'                => $jobTotal,
                'status'                   => 'completed',
                'payment_status'           => 'paid',
                'amount_paid'              => $jobTotal,
                'completed_at'             => $completedAt,
                'material_tech'            => $materialTech,
                'material_provider'        => $materialProvider,
                'material_cost'            => $materialTech + $materialProvider,
                'is_special_closing'       => $isSpecialClosing ? 1 : 0,
                'provider_retained_amount' => $providerRetainedAmount,
                'payment_method'           => $pmValid ? $paymentMethod : null,
            ]);

            $this->db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'errors' => ['lead' => ['Transaction Failed: ' . $e->getMessage()]]];
        }
    }

    public function editMaterials(int $id, array $input): array
    {
        $lead = $this->repo->findById($id);
        if ($lead === null) {
            return ['success' => false, 'errors' => ['lead' => ['Lead not found.']]];
        }

        $jobTotal      = !empty($input['job_total']) ? (float) $input['job_total'] : (float) $lead['job_total'];
        $paymentMethod = $input['payment_method'] ?? null;
        $pmValid       = in_array($paymentMethod, ['cash', 'credit_card', 'check', 'zelle', 'cash_app'], true);

        $contractorId = !empty($lead['contractor_id']) ? (int) $lead['contractor_id'] : null;

        $this->db->beginTransaction();
        try {
            // 1. Refund existing materials' stock
            foreach ($this->repo->getMaterials($id) as $existing) {
                $invId = !empty($existing['inventory_id']) ? (int) $existing['inventory_id'] : null;
                if ($invId !== null && !empty($existing['location_id'])) {
                    $this->inventoryRepo->incrementStock($invId, (float) $existing['qty'], (int) $existing['location_id']);
                }
            }

            // 2. Delete existing materials
            $this->repo->deleteMaterials($id);

            // 3. Insert new materials & deduct stock
            $materialTech     = 0.0;
            $materialProvider = 0.0;

            foreach ($input['materials'] ?? [] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $invId      = !empty($row['inventory_id']) && $row['inventory_id'] !== 'null' ? (int) $row['inventory_id'] : null;
                $locationId = $invId !== null ? (int) ($row['location_id'] ?? 1) : null;

                if ($invId === null) {
                    $rawCost = (float) ($row['unit_cost'] ?? 0);
                    if ($rawCost <= 0) {
                        continue;
                    }
                    $desc = 'Manual Entry';
                    $qty  = 1.0;
                } else {
                    $desc = trim($row['description'] ?? '');
                    $qty  = max(0.01, (float) ($row['qty'] ?? 1));
                }

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

                if ($invId !== null && $locationId !== null) {
                    $this->inventoryRepo->decrementStock($invId, $qty, $locationId);
                }

                if ($ownership === 'company') {
                    $materialProvider += $lineTotal;
                } else {
                    $materialTech += $lineTotal;
                }
            }

            $isSpecialClosing       = !empty($lead['is_special_closing']);
            $providerRetainedAmount = isset($lead['provider_retained_amount']) ? (float) $lead['provider_retained_amount'] : null;

            $this->calculateAndSaveFinancials(
                $id, $lead, $jobTotal, $materialTech, $materialProvider,
                $pmValid ? $paymentMethod : 'cash', $isSpecialClosing, $providerRetainedAmount
            );

            $completedAt = $this->normalizeDatetime($input['completed_at'] ?? '');
            if ($completedAt === '') {
                $completedAt = !empty($lead['completed_at']) ? $lead['completed_at'] : date('Y-m-d H:i:s');
            }

            $this->repo->update($id, [
                'job_total'         => $jobTotal,
                'amount_paid'       => $jobTotal,
                'completed_at'      => $completedAt,
                'material_tech'     => $materialTech,
                'material_provider' => $materialProvider,
                'material_cost'     => $materialTech + $materialProvider,
                'payment_method'    => $pmValid ? $paymentMethod : null,
            ]);

            $this->db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'errors' => ['lead' => ['Transaction Failed: ' . $e->getMessage()]]];
        }
    }

    public function duplicate(int $id): array
    {
        $lead = $this->repo->findById($id);
        if ($lead === null) {
            return ['success' => false, 'id' => null];
        }
        $copy = [
            'customer_id'      => $lead['customer_id'],
            'lead_source_id'   => $lead['lead_source_id'] ?: null,
            'contractor_id'    => $lead['contractor_id']  ?: null,
            'description'      => $lead['description'] ?? '',
            'scheduled_date'   => $lead['scheduled_date'] ?? date('Y-m-d'),
            'scheduled_time'   => $lead['scheduled_time'] ?? date('H:i:s'),
            'status'           => 'progress',
            'job_total'        => 0.00,
            'payment_status'   => 'unpaid',
            'amount_paid'      => 0.00,
            'company_id'       => $lead['company_id'] ?? null,
            'job_type'         => $lead['job_type'] ?? null,
            'duration_hours'   => (int) ($lead['duration_hours'] ?? 1),
            'deposit_amount'   => 0.00,
            'deposit_received' => 0,
            'address'          => $lead['address'] ?? '',
            'city'             => $lead['city']    ?? '',
            'state'            => $lead['state']   ?? '',
            'zip'              => $lead['zip']     ?? '',
            'lat'              => isset($lead['lat']) && $lead['lat'] !== '' ? (float) $lead['lat'] : null,
            'lng'              => isset($lead['lng']) && $lead['lng'] !== '' ? (float) $lead['lng'] : null,
        ];
        
        $newId = $this->repo->create($copy);
        return ['success' => true, 'id' => $newId];
    }

    public function delete(int $id): bool
    {
        $this->db->table('invoices')->where('job_id', $id)->delete();
        return $this->repo->delete($id);
    }

    public function getFormData(): array
    {
        return [
            'customers'    => $this->db->table('customers')->orderBy('full_name')->get(),
            'lead_sources' => $this->db->table('lead_sources')->where('is_active', 1)->orderBy('name')->get(),
            'contractors'  => $this->db->table('contractors')->where('is_active', 1)->orderBy('full_name')->get(),
            'companies'    => $this->db->table('companies')->where('is_active', 1)->orderBy('name')->get(),
        ];
    }

    private function resolveTime(array $data): string
    {
        $hour = (int) ($data['scheduled_hour'] ?? 0);
        $ampm = strtoupper($data['scheduled_ampm'] ?? 'AM');
        if ($hour >= 1 && $hour <= 12) {
            if ($ampm === 'PM' && $hour !== 12) {
                $hour += 12;
            } elseif ($ampm === 'AM' && $hour === 12) {
                $hour = 0;
            }
            return sprintf('%02d:00:00', $hour);
        }
        return date('H:i:s');
    }

    private function sanitize(array $data): array
    {
        $rawStatus = $data['status'] ?? '';
        if ($rawStatus === 'deposit') {
            $rawStatus = 'progress';
        }
        $status = in_array($rawStatus, ['estimate','progress','scheduled','canceled','completed','pending_payment'])
            ? $rawStatus : 'progress';

        $sanitized = [
            'customer_id'    => (int) $data['customer_id'],
            'lead_source_id' => !empty($data['lead_source_id']) ? (int) $data['lead_source_id'] : null,
            'contractor_id'  => !empty($data['contractor_id'])  ? (int) $data['contractor_id']  : null,
            'description'    => trim($data['description'] ?? ''),
            'scheduled_date' => !empty($data['scheduled_date']) ? $data['scheduled_date'] : date('Y-m-d'),
            'scheduled_time' => ($status === 'scheduled') ? $this->resolveTime($data) : date('H:i:s'),
            'status'         => $status,
            'job_total'      => !empty($data['job_total']) ? (float) $data['job_total'] : 0.00,
            'payment_status' => in_array($data['payment_status'] ?? '', ['unpaid','partial','paid']) ? $data['payment_status'] : 'unpaid',
            'amount_paid'    => (float) ($data['amount_paid'] ?? 0),
        ];

        $companyId = !empty($data['company_id']) ? (int) $data['company_id'] : null;
        $sanitized['company_id'] = $companyId;
        $sanitized['job_type']   = null;

        $sanitized['address'] = trim($data['address'] ?? '');
        $sanitized['city']    = trim($data['city']    ?? '');
        $sanitized['state']   = trim($data['state']   ?? '');
        $sanitized['zip']     = trim($data['zip']     ?? '');
        $sanitized['lat']     = isset($data['lat']) && $data['lat'] !== '' ? (float) $data['lat'] : null;
        $sanitized['lng']     = isset($data['lng']) && $data['lng'] !== '' ? (float) $data['lng'] : null;

        return $sanitized;
    }
}
