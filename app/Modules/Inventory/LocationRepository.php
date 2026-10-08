<?php
declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Core\QueryBuilder;

class LocationRepository
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function findAll(): array
    {
        return $this->db->raw(
            "SELECT il.location_id, il.name, il.type, il.contractor_id,
                    c.full_name AS contractor_name,
                    COUNT(DISTINCT CASE WHEN s.quantity > 0 THEN s.inventory_id END) AS stocked_items,
                    il.created_at, il.updated_at
             FROM inventory_locations il
             LEFT JOIN contractors c ON c.contractor_id = il.contractor_id
             LEFT JOIN inventory_stocks s ON s.location_id = il.location_id
             GROUP BY il.location_id, il.name, il.type, il.contractor_id,
                      c.full_name, il.created_at, il.updated_at
             ORDER BY FIELD(il.type,'warehouse','vehicle','tech'), il.name ASC"
        );
    }

    public function findById(int $id): array|null
    {
        return $this->db->rawFirst(
            "SELECT il.location_id, il.name, il.type, il.contractor_id,
                    c.full_name AS contractor_name,
                    il.created_at, il.updated_at
             FROM inventory_locations il
             LEFT JOIN contractors c ON c.contractor_id = il.contractor_id
             WHERE il.location_id = :id",
            [':id' => $id]
        );
    }

    public function create(array $data): int
    {
        return $this->db->table('inventory_locations')->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table('inventory_locations')->where('location_id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->table('inventory_locations')->where('location_id', $id)->delete();
    }
}
