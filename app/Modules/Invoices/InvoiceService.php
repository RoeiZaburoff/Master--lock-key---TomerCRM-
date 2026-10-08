<?php
declare(strict_types=1);

namespace App\Modules\Invoices;

use App\Core\QueryBuilder;

class InvoiceService
{
    private InvoiceRepository $repo;
    private QueryBuilder      $db;

    public function __construct()
    {
        $this->repo = new InvoiceRepository();
        $this->db   = new QueryBuilder();
    }

    public function getAll(): array
    {
        return $this->repo->findAll();
    }

    public function getById(int $id): array|null
    {
        return $this->repo->findById($id);
    }

    public function getCreateData(int $jobId): array|null
    {
        $compJoin     = "LEFT JOIN companies comp ON comp.company_id = j.company_id";
        $compCols     = ", comp.name AS company_name, comp.phone AS company_phone, comp.address AS company_address, comp.logo_path AS company_logo, comp.email AS company_email";

        $job = $this->db->rawFirst(
            "SELECT j.*, c.full_name AS customer_name, c.phone, c.email,
                    c.address_line1, c.address_line2, c.city, c.state, c.zip
                    {$compCols}
             FROM jobs j
             JOIN customers c ON c.customer_id = j.customer_id
             {$compJoin}
             WHERE j.job_id = :id",
            [':id' => $jobId]
        );
        if ($job === null) {
            return null;
        }
        $existing = $this->repo->findByJobId($jobId);
        return ['job' => $job, 'existing' => $existing, 'next_number' => $this->repo->getNextNumber()];
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }

    public function create(array $data): array
    {
        $jobId   = (int) $data['job_id'];
        $taxRate = (float) ($data['tax_rate'] ?? 0);
        $items   = $this->parseItems($data['items'] ?? []);

        $subtotal = count($items) > 0
            ? array_sum(array_column($items, 'amount'))
            : (float) ($data['subtotal'] ?? 0);

        $taxAmt  = round($subtotal * $taxRate, 2);
        $total   = round($subtotal + $taxAmt, 2);
        $deposit = $this->parseDeposit($data['deposit_amount'] ?? '');

        $row = [
            'job_id'         => $jobId,
            'invoice_number' => $this->repo->getNextNumber(),
            'issued_date'    => $data['issued_date'] ?? date('Y-m-d'),
            'due_date'       => !empty($data['due_date']) ? $data['due_date'] : null,
            'subtotal'       => $subtotal,
            'tax_rate'       => $taxRate,
            'tax_amount'     => $taxAmt,
            'total'          => $total,
            'notes'          => trim($data['notes'] ?? ''),
        ];

        $this->applyPaymentFields($row, $data, $deposit);

        $this->db->beginTransaction();
        try {
            $this->db->rawFirst('SELECT job_id FROM jobs WHERE job_id=:id FOR UPDATE', [':id'=>$jobId]);
            $dataForSnapshot=$this->getCreateData($jobId);
            if ($dataForSnapshot === null || $dataForSnapshot['existing'] !== null) { throw new \RuntimeException('Invoice already exists or job is missing.'); }
            $row['document_snapshot']=DocumentSnapshot::capture($dataForSnapshot['job']);
            $id = $this->repo->create($row);

            if (!empty($items)) {
                $this->repo->saveItems($id, $items);
            }

            $this->db->commit();
            return ['success' => true, 'id' => $id];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->repo->findById($id);
        if ($existing === null) {
            return ['success' => false, 'error' => 'Invoice not found.'];
        }

        $taxRate = (float) ($data['tax_rate'] ?? 0);
        $items   = $this->parseItems($data['items'] ?? []);

        $subtotal = count($items) > 0
            ? array_sum(array_column($items, 'amount'))
            : (float) ($data['subtotal'] ?? 0);

        $taxAmt  = round($subtotal * $taxRate, 2);
        $total   = round($subtotal + $taxAmt, 2);
        $deposit = $this->parseDeposit($data['deposit_amount'] ?? '');

        $row = [
            'issued_date' => $data['issued_date'] ?? $existing['issued_date'],
            'due_date'    => !empty($data['due_date']) ? $data['due_date'] : null,
            'subtotal'    => $subtotal,
            'tax_rate'    => $taxRate,
            'tax_amount'  => $taxAmt,
            'total'       => $total,
            'notes'       => trim($data['notes'] ?? ''),
        ];

        $this->applyPaymentFields($row, $data, $deposit);

        $this->repo->update($id, $row);

        if (!empty($items)) {
            $this->repo->saveItems($id, $items);
        }

        return ['success' => true, 'id' => $id];
    }

    // ----------------------------------------------------------------

    private function parseDeposit(mixed $raw): float|null
    {
        $val = trim((string) $raw);
        if ($val === '' || $val === '0' || $val === '0.00') {
            return null;
        }
        $f = (float) $val;
        return $f > 0 ? round($f, 2) : null;
    }

    private function applyPaymentFields(array &$row, array $data, float|null $deposit): void
    {
        $row['deposit_amount'] = $deposit;

        $method = $data['payment_method'] ?? '';
        $row['payment_method'] = in_array($method, ['credit_card', 'check', 'cash', 'zelle', 'cash_app'], true)
            ? $method : null;

        $ref = trim($data['payment_reference'] ?? '');
        $row['payment_reference'] = $ref !== '' ? $ref : null;
    }

    private function parseItems(array $raw): array
    {
        $items = [];
        foreach ($raw as $item) {
            $desc = trim($item['description'] ?? '');
            if ($desc === '') {
                continue;
            }
            $items[] = [
                'description' => $desc,
                'amount'      => round((float) ($item['amount'] ?? 0), 2),
            ];
        }
        return $items;
    }
}
