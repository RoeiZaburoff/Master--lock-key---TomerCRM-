<?php
declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Contracts\RepositoryInterface;
use App\Core\QueryBuilder;

class CategoryRepository implements RepositoryInterface
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function findById(int $id): array|null
    {
        return $this->db->table('inventory_categories')->where('category_id', $id)->first();
    }

    public function findAll(array $filters = []): array
    {
        return $this->db->table('inventory_categories')->orderBy('name', 'ASC')->get();
    }

    public function create(array $data): int
    {
        return $this->db->table('inventory_categories')->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table('inventory_categories')->where('category_id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->table('inventory_categories')->where('category_id', $id)->delete();
    }
}
