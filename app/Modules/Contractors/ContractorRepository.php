<?php
declare(strict_types=1);

namespace App\Modules\Contractors;

use App\Contracts\RepositoryInterface;
use App\Core\QueryBuilder;

class ContractorRepository implements RepositoryInterface
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function findById(int $id): array|null
    {
        return $this->db->table('contractors')->where('contractor_id', $id)->first();
    }

    public function findAll(array $filters = []): array
    {
        return $this->db->table('contractors')->orderBy('created_at', 'DESC')->get();
    }

    public function create(array $data): int
    {
        return $this->db->table('contractors')->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table('contractors')->where('contractor_id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->table('contractors')->where('contractor_id', $id)->delete();
    }
}
