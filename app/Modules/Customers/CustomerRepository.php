<?php
declare(strict_types=1);

namespace App\Modules\Customers;

use App\Contracts\RepositoryInterface;
use App\Core\QueryBuilder;

class CustomerRepository implements RepositoryInterface
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function findById(int $id): array|null
    {
        return $this->db->table('customers')->where('customer_id', $id)->first();
    }

    public function findAll(array $filters = []): array
    {
        $this->db->table('customers');
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            return $this->db->raw(
                "SELECT * FROM customers
                 WHERE full_name LIKE :s1 OR phone LIKE :s2 OR city LIKE :s3 OR zip LIKE :s4
                 ORDER BY created_at DESC",
                [':s1' => $s, ':s2' => $s, ':s3' => $s, ':s4' => $s]
            );
        }
        return $this->db->orderBy('created_at', 'DESC')->get();
    }

    public function create(array $data): int
    {
        return $this->db->table('customers')->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table('customers')->where('customer_id', $id)->update($data);
    }

    public function countJobs(int $customerId): int
    {
        $row = $this->db->rawFirst(
            "SELECT COUNT(*) AS cnt FROM jobs WHERE customer_id = :id",
            [':id' => $customerId]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function delete(int $id): bool
    {
        return $this->db->table('customers')->where('customer_id', $id)->delete();
    }

    public function findByPhone(string $phone): array|null
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 11 && $digits[0] === '1') $digits = substr($digits, 1);
        if (strlen($digits) !== 10) return null;

        $formattedPhone = sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 4));
        $altFormatted   = sprintf('%s-%s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 4));

        return $this->db->rawFirst(
            "SELECT * FROM customers WHERE phone = :f1 OR phone = :f2 OR phone = :d LIMIT 1",
            [':f1' => $formattedPhone, ':f2' => $altFormatted, ':d' => $digits]
        );
    }

    public function findByAddress(string $address): array|null
    {
        $address = trim($address);
        if ($address === '') return null;
        return $this->db->rawFirst(
            "SELECT * FROM customers WHERE address_line1 LIKE :a LIMIT 1",
            [':a' => $address . '%']
        );
    }

    public function findByEmail(string $email): array|null
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }
        return $this->db->rawFirst(
            "SELECT * FROM customers WHERE LOWER(email) = :e LIMIT 1",
            [':e' => $email]
        );
    }

    public function getJobHistory(int $customerId): array
    {
        $compJoin = "LEFT JOIN companies comp ON comp.company_id = j.company_id";
        $compCol  = ", comp.name AS company_name";

        return $this->db->raw(
            "SELECT j.*, COALESCE(ls.name, 'Direct') AS lead_source_name,
                    COALESCE(c.full_name, 'Owner') AS contractor_name
                    {$compCol}
             FROM jobs j
             LEFT JOIN lead_sources ls ON ls.lead_source_id = j.lead_source_id
             LEFT JOIN contractors c ON c.contractor_id = j.contractor_id
             {$compJoin}
             WHERE j.customer_id = :cid
             ORDER BY j.created_at DESC",
            [':cid' => $customerId]
        );
    }
}
