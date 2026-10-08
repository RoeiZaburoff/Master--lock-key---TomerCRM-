<?php
declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\QueryBuilder;

class CompanyRepository
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function findAll(): array
    {
        return $this->db->table('companies')->orderBy('created_at', 'DESC')->get();
    }

    public function findAllActive(): array
    {
        return $this->db->table('companies')->where('is_active', 1)->orderBy('name')->get();
    }

    public function findById(int $id): array|null
    {
        return $this->db->table('companies')->where('company_id', $id)->first();
    }

    public function create(array $data): int
    {
        return $this->db->table('companies')->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table('companies')->where('company_id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->table('companies')->where('company_id', $id)->delete();
    }
}
