<?php
declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Contracts\RepositoryInterface;
use App\Core\QueryBuilder;

class InventoryRepository implements RepositoryInterface
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function findById(int $id): array|null
    {
        return $this->db->rawFirst(
            "SELECT i.*, m.name AS manufacturer_name, c.name AS category_name,
                    COALESCE(s.quantity, 0) AS quantity,
                    COALESCE(s.quantity, 0) AS stock_qty
             FROM inventory i
             LEFT JOIN manufacturers m ON m.manufacturer_id = i.manufacturer_id
             LEFT JOIN inventory_categories c ON c.category_id = i.category_id
             LEFT JOIN inventory_stocks s ON s.inventory_id = i.inventory_id AND s.location_id = 1
             WHERE i.inventory_id = :id",
            [':id' => $id]
        );
    }

    public function findAll(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['manufacturer_id'])) {
            $where[]                    = 'i.manufacturer_id = :manufacturer_id';
            $params[':manufacturer_id'] = (int) $filters['manufacturer_id'];
        }
        if (!empty($filters['search'])) {
            $s                = '%' . $filters['search'] . '%';
            $where[]          = '(i.name LIKE :s OR i.fcc_id LIKE :s2 OR i.part_number LIKE :s3 OR i.product_id = :s4 OR i.ic LIKE :s5 OR i.chip LIKE :s6)';
            $params[':s']     = $s;
            $params[':s2']    = $s;
            $params[':s3']    = $s;
            $params[':s4']    = $filters['search'];
            $params[':s5']    = $s;
            $params[':s6']    = $s;
        }

        return $this->db->raw(
            "SELECT i.*, m.name AS manufacturer_name, c.name AS category_name,
                    COALESCE(s.quantity, 0) AS quantity,
                    COALESCE(s.quantity, 0) AS stock_qty
             FROM inventory i
             LEFT JOIN manufacturers m ON m.manufacturer_id = i.manufacturer_id
             LEFT JOIN inventory_categories c ON c.category_id = i.category_id
             LEFT JOIN inventory_stocks s ON s.inventory_id = i.inventory_id AND s.location_id = 1
             WHERE " . implode(' AND ', $where) . "
             ORDER BY COALESCE(c.name, 'Uncategorized') ASC, COALESCE(m.name, 'No Brand') ASC, i.name ASC",
            $params
        );
    }

    // stock_qty = stock at the requested location; quantity = Main Warehouse stock always.
    public function findAllForLocation(int $locationId, array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['manufacturer_id'])) {
            $where[]                    = 'i.manufacturer_id = :manufacturer_id';
            $params[':manufacturer_id'] = (int) $filters['manufacturer_id'];
        }
        if (!empty($filters['search'])) {
            $s                = '%' . $filters['search'] . '%';
            $where[]          = '(i.name LIKE :s OR i.fcc_id LIKE :s2 OR i.part_number LIKE :s3 OR i.product_id = :s4 OR i.ic LIKE :s5 OR i.chip LIKE :s6)';
            $params[':s']     = $s;
            $params[':s2']    = $s;
            $params[':s3']    = $s;
            $params[':s4']    = $filters['search'];
            $params[':s5']    = $s;
            $params[':s6']    = $s;
        }

        $whereStr = implode(' AND ', $where);

        $orderBy = "ORDER BY COALESCE(c.name, 'Uncategorized') ASC, COALESCE(m.name, 'No Brand') ASC, i.name ASC";

        if ($locationId === 1) {
            $sql = "SELECT i.*, m.name AS manufacturer_name, c.name AS category_name,
                           COALESCE(s.quantity, 0) AS quantity,
                           COALESCE(s.quantity, 0) AS stock_qty
                    FROM inventory i
                    LEFT JOIN manufacturers m ON m.manufacturer_id = i.manufacturer_id
                    LEFT JOIN inventory_categories c ON c.category_id = i.category_id
                    LEFT JOIN inventory_stocks s ON s.inventory_id = i.inventory_id AND s.location_id = 1
                    WHERE {$whereStr}
                    {$orderBy}";
        } else {
            $params[':lid'] = $locationId;
            $sql = "SELECT i.*, m.name AS manufacturer_name, c.name AS category_name,
                           COALESCE(main.quantity, 0) AS quantity,
                           COALESCE(loc.quantity, 0)  AS stock_qty
                    FROM inventory i
                    LEFT JOIN manufacturers m ON m.manufacturer_id = i.manufacturer_id
                    LEFT JOIN inventory_categories c ON c.category_id = i.category_id
                    LEFT JOIN inventory_stocks main ON main.inventory_id = i.inventory_id AND main.location_id = 1
                    LEFT JOIN inventory_stocks loc  ON loc.inventory_id  = i.inventory_id AND loc.location_id  = :lid
                    WHERE {$whereStr}
                    {$orderBy}";
        }

        return $this->db->raw($sql, $params);
    }

    public function create(array $data): int
    {
        return $this->db->table('inventory')->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table('inventory')->where('inventory_id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->table('inventory')->where('inventory_id', $id)->delete();
    }

    public function getStock(int $inventoryId, int $locationId): float
    {
        $row = $this->db->rawFirst(
            "SELECT quantity FROM inventory_stocks WHERE inventory_id = :id AND location_id = :lid",
            [':id' => $inventoryId, ':lid' => $locationId]
        );
        return $row !== null ? (float) $row['quantity'] : 0.0;
    }

    public function upsertStock(int $inventoryId, int $locationId, float $quantity): void
    {
        $this->db->rawExecute(
            "INSERT INTO inventory_stocks (inventory_id, location_id, quantity)
             VALUES (:id, :lid, :qty)
             ON DUPLICATE KEY UPDATE quantity = :qty2",
            [
                ':id'  => (int) $inventoryId,
                ':lid' => (int) $locationId,
                ':qty' => (float) $quantity,
                ':qty2'=> (float) $quantity,
            ]
        );
    }

    public function decrementStock(int $inventoryId, float $qty, int $locationId): void
    {
        $this->db->rawExecute(
            "UPDATE inventory_stocks
             SET quantity = GREATEST(0, quantity - :qty)
             WHERE inventory_id = :id AND location_id = :lid",
            [
                ':qty' => (float) $qty,
                ':id'  => (int) $inventoryId,
                ':lid' => (int) $locationId,
            ]
        );
    }

    public function incrementStock(int $inventoryId, float $qty, int $locationId): void
    {
        $this->db->rawExecute(
            "INSERT INTO inventory_stocks (inventory_id, location_id, quantity)
             VALUES (:id, :lid, :qty)
             ON DUPLICATE KEY UPDATE quantity = quantity + :qty2",
            [
                ':id'  => (int) $inventoryId,
                ':lid' => (int) $locationId,
                ':qty' => (float) $qty,
                ':qty2'=> (float) $qty,
            ]
        );
    }

    public function assignProductId(int $id): void
    {
        $productId = 'P' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
        $this->db->rawExecute(
            "UPDATE inventory SET product_id = :pid WHERE inventory_id = :id AND product_id IS NULL",
            [':pid' => $productId, ':id' => $id]
        );
    }

    public function logTransfer(int $inventoryId, int $fromLocationId, int $toLocationId, float $qty): void
    {
        $this->db->rawExecute(
            "INSERT INTO inventory_transfers (inventory_id, from_location_id, to_location_id, qty)
             VALUES (:iid, :from, :to, :qty)",
            [
                ':iid'  => (int) $inventoryId,
                ':from' => (int) $fromLocationId,
                ':to'   => (int) $toLocationId,
                ':qty'  => (float) $qty,
            ]
        );
    }
}
