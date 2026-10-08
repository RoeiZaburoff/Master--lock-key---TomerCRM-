<?php
declare(strict_types=1);

namespace App\Modules\Invoices;

use App\Contracts\RepositoryInterface;
use App\Core\QueryBuilder;

class InvoiceRepository implements RepositoryInterface
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    private function companyJoin(): string
    {
        return "LEFT JOIN companies comp ON comp.company_id = j.company_id";
    }

    private function companyCols(): string
    {
        return ",\n comp.name AS company_name, comp.phone AS company_phone,
                    comp.address AS company_address, comp.logo_path AS company_logo,
                    comp.email AS company_email";
    }

    private function companyColShort(): string
    {
        return ", JSON_UNQUOTE(JSON_EXTRACT(i.document_snapshot,'$.company_name')) AS company_name";
    }

    public function findById(int $id): array|null
    {
        $row = $this->db->rawFirst(
            "SELECT i.*, j.job_type, j.job_total, j.status AS job_status,
                    j.description AS job_description,
                    c.full_name AS customer_name, c.phone AS customer_phone,
                    c.email AS customer_email,
                    c.address_line1, c.address_line2, c.city, c.state, c.zip
                    {$this->companyCols()}
             FROM invoices i
             JOIN jobs j ON j.job_id = i.job_id
             JOIN customers c ON c.customer_id = j.customer_id
             {$this->companyJoin()}
             WHERE i.invoice_id = :id",
            [':id' => $id]
        );
        if ($row !== null) {
            $row = DocumentSnapshot::apply($row);
            $row['items'] = $this->findItems((int) $row['invoice_id']);
        }
        return $row;
    }

    public function findAll(array $filters = []): array
    {
        return $this->db->raw(
            "SELECT i.*, c.full_name AS customer_name, j.job_type
                    {$this->companyColShort()}
             FROM invoices i
             JOIN jobs j ON j.job_id = i.job_id
             JOIN customers c ON c.customer_id = j.customer_id
             {$this->companyJoin()}
             ORDER BY i.created_at DESC"
        );
    }

    public function findItems(int $invoiceId): array
    {
        return $this->db->raw(
            "SELECT * FROM invoice_items WHERE invoice_id = :id ORDER BY sort_order ASC, item_id ASC",
            [':id' => $invoiceId]
        );
    }

    public function saveItems(int $invoiceId, array $items): void
    {
        $this->db->rawExecute("DELETE FROM invoice_items WHERE invoice_id = :id", [':id' => $invoiceId]);
        foreach ($items as $i => $item) {
            $desc = trim($item['description'] ?? '');
            if ($desc === '') {
                continue;
            }
            $this->db->table('invoice_items')->insert([
                'invoice_id'  => $invoiceId,
                'description' => $desc,
                'amount'      => round((float) ($item['amount'] ?? 0), 2),
                'sort_order'  => $i,
            ]);
        }
    }

    public function findByJobId(int $jobId): array|null
    {
        return $this->db->table('invoices')->where('job_id', $jobId)->first();
    }

    public function create(array $data): int
    {
        return $this->db->table('invoices')->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table('invoices')->where('invoice_id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->table('invoices')->where('invoice_id', $id)->delete();
    }

    public function getNextNumber(): string
    {
        return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(6)));
    }
}
