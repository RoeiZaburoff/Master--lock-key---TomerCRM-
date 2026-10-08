<?php
declare(strict_types=1);

namespace App\Modules\Materials;

use App\Contracts\RepositoryInterface;
use App\Core\QueryBuilder;

class MaterialRepository implements RepositoryInterface
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function findById(int $id): array|null
    {
        return $this->db->table('materials')->where('material_id', $id)->first();
    }

    public function findAll(array $filters = []): array
    {
        return $this->db->table('materials')->orderBy('created_at', 'DESC')->get();
    }

    public function create(array $data): int
    {
        return $this->db->table('materials')->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table('materials')->where('material_id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->table('materials')->where('material_id', $id)->delete();
    }
}
