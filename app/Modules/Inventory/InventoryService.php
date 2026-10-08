<?php
declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Core\QueryBuilder;
use App\Core\Validator;

class InventoryService
{
    private InventoryRepository    $repo;
    private ManufacturerRepository $manufacturerRepo;
    private CategoryRepository     $categoryRepo;
    private LocationRepository     $locationRepo;
    private QueryBuilder           $db;

    public function __construct()
    {
        $this->repo             = new InventoryRepository();
        $this->manufacturerRepo = new ManufacturerRepository();
        $this->categoryRepo     = new CategoryRepository();
        $this->locationRepo     = new LocationRepository();
        $this->db               = new QueryBuilder();
    }

    public function getAll(array $filters = []): array
    {
        return $this->repo->findAll($filters);
    }

    public function getAllForLocation(int $locationId, array $filters = []): array
    {
        return $this->repo->findAllForLocation($locationId, $filters);
    }

    public function getById(int $id): array|null
    {
        return $this->repo->findById($id);
    }

    public function getLocations(): array
    {
        return $this->locationRepo->findAll();
    }

    public function getCatalog(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, array $files = []): array
    {
        $v = new Validator();
        if (!$v->validate($data, $this->rules())) {
            return ['success' => false, 'errors' => $v->errors()];
        }

        $sanitized = $this->sanitize($data);
        $imagePath = $this->handleImageUpload($files);
        if ($imagePath !== null) {
            $sanitized['image_path'] = $imagePath;
        }

        $id = $this->repo->create($sanitized);
        $this->repo->assignProductId($id);

        $initialQty = max(0.0, (float) ($data['initial_qty'] ?? 0));
        if ($initialQty > 0) {
            $this->repo->upsertStock($id, 1, $initialQty);
        }

        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data, array $files = []): array
    {
        $v = new Validator();
        if (!$v->validate($data, $this->rules())) {
            return ['success' => false, 'errors' => $v->errors()];
        }

        $sanitized = $this->sanitize($data);
        $imagePath = $this->handleImageUpload($files);
        if ($imagePath !== null) {
            $sanitized['image_path'] = $imagePath;
        }

        $this->repo->update($id, $sanitized);
        return ['success' => true];
    }

    public function delete(int $id): bool
    {
        $item = $this->repo->findById($id);
        if ($item !== null && !empty($item['image_path'])) {
            $full = STORAGE_PATH . '/inventory/' . basename($item['image_path']);
            if (is_file($full)) {
                @unlink($full);
            }
        }
        return $this->repo->delete($id);
    }

    public function adjustStock(int $inventoryId, int $locationId, float $newQty): void
    {
        $this->repo->upsertStock($inventoryId, $locationId, max(0.0, $newQty));
    }

    public function transferStock(int $inventoryId, int $fromLocationId, int $toLocationId, float $qty): array
    {
        if ($qty <= 0) {
            return ['success' => false, 'errors' => ['qty' => ['Quantity must be greater than zero.']]];
        }
        if ($fromLocationId === $toLocationId) {
            return ['success' => false, 'errors' => ['from' => ['Source and destination must be different.']]];
        }

        $available = $this->repo->getStock($inventoryId, $fromLocationId);
        if ($qty > $available) {
            return ['success' => false, 'errors' => ['qty' => ['Insufficient stock. Available: ' . number_format($available, 2)]]];
        }

        $this->db->beginTransaction();
        try {
            $this->repo->decrementStock($inventoryId, $qty, $fromLocationId);
            $this->repo->incrementStock($inventoryId, $qty, $toLocationId);
            $this->repo->logTransfer($inventoryId, $fromLocationId, $toLocationId, $qty);
            $this->db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'errors' => ['transfer' => ['Transfer failed. Please try again.']]];
        }
    }

    public function transferBulkStock(array $items, int $fromLocationId, int $toLocationId): array
    {
        if (empty($items)) {
            return ['success' => false, 'error' => 'No items selected.'];
        }
        if ($fromLocationId < 1 || $toLocationId < 1) {
            return ['success' => false, 'error' => 'Invalid location.'];
        }
        if ($fromLocationId === $toLocationId) {
            return ['success' => false, 'error' => 'Source and destination must differ.'];
        }

        // Validate all items before opening a transaction
        foreach ($items as $inventoryId => $qty) {
            $inventoryId = (int) $inventoryId;
            $qty         = (float) $qty;
            if ($qty <= 0) {
                return ['success' => false, 'error' => "Quantity must be greater than zero (item #{$inventoryId})."];
            }
            $available = $this->repo->getStock($inventoryId, $fromLocationId);
            if ($qty > $available) {
                return [
                    'success' => false,
                    'error'   => "Insufficient stock for item #{$inventoryId}. Available: " . number_format($available, 2) . ".",
                ];
            }
        }

        $this->db->beginTransaction();
        try {
            foreach ($items as $inventoryId => $qty) {
                $inventoryId = (int) $inventoryId;
                $qty         = (float) $qty;
                $this->repo->decrementStock($inventoryId, $qty, $fromLocationId);
                $this->repo->incrementStock($inventoryId, $qty, $toLocationId);
                $this->repo->logTransfer($inventoryId, $fromLocationId, $toLocationId, $qty);
            }
            $this->db->commit();
            return ['success' => true, 'count' => count($items)];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Transaction failed. Please try again.'];
        }
    }

    // $locationId defaults to 1 (Main Warehouse) for callers outside this module.
    public function deductStock(int $inventoryId, float $qty, int $locationId = 1): void
    {
        $this->repo->decrementStock($inventoryId, $qty, $locationId);
    }

    public function refundStock(int $inventoryId, float $qty, int $locationId = 1): void
    {
        $this->repo->incrementStock($inventoryId, $qty, $locationId);
    }

    public function getManufacturers(): array
    {
        return $this->manufacturerRepo->findAll();
    }

    public function getManufacturerById(int $id): array|null
    {
        return $this->manufacturerRepo->findById($id);
    }

    public function createManufacturer(array $data): array
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'errors' => ['name' => ['Name is required.']]];
        }
        $id = $this->manufacturerRepo->create(['name' => $name]);
        return ['success' => true, 'id' => $id];
    }

    public function updateManufacturer(int $id, array $data): array
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'errors' => ['name' => ['Name is required.']]];
        }
        $this->manufacturerRepo->update($id, ['name' => $name]);
        return ['success' => true];
    }

    public function deleteManufacturer(int $id): bool
    {
        return $this->manufacturerRepo->delete($id);
    }

    public function getCategories(): array
    {
        return $this->categoryRepo->findAll();
    }

    public function getCategoryById(int $id): array|null
    {
        return $this->categoryRepo->findById($id);
    }

    public function createCategory(array $data): array
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'errors' => ['name' => ['Name is required.']]];
        }
        $id = $this->categoryRepo->create(['name' => $name]);
        return ['success' => true, 'id' => $id];
    }

    public function updateCategory(int $id, array $data): array
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'errors' => ['name' => ['Name is required.']]];
        }
        $this->categoryRepo->update($id, ['name' => $name]);
        return ['success' => true];
    }

    public function deleteCategory(int $id): bool
    {
        return $this->categoryRepo->delete($id);
    }

    private function rules(): array
    {
        return [
            'name' => 'required|max:150',
            'cost' => 'required|numeric|min:0',
        ];
    }

    private function sanitize(array $data): array
    {
        return [
            'name'            => trim($data['name']),
            'fcc_id'          => trim($data['fcc_id'] ?? '') !== '' ? trim($data['fcc_id']) : null,
            'part_number'     => trim($data['part_number'] ?? '') !== '' ? trim($data['part_number']) : null,
            'ic'              => trim($data['ic'] ?? '') !== '' ? trim($data['ic']) : null,
            'chip'            => trim($data['chip'] ?? '') !== '' ? trim($data['chip']) : null,
            'frequency'       => trim($data['frequency'] ?? '') !== '' ? trim($data['frequency']) : null,
            'manufacturer_id' => !empty($data['manufacturer_id']) ? (int) $data['manufacturer_id'] : null,
            'category_id'     => !empty($data['category_id'])     ? (int) $data['category_id']     : null,
            'cost'            => (float) $data['cost'],
        ];
    }

    private function handleImageUpload(array $files): ?string
    {
        if (empty($files['image']['name']) || $files['image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $files['image'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return null;
        }

        $dir = STORAGE_PATH . '/inventory';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = uniqid('inv_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            return null;
        }

        return 'storage/inventory/' . $filename;
    }
}
