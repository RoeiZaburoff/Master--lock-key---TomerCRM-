<?php
declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Core\QueryBuilder;

class LocationService
{
    private LocationRepository $repo;

    public function __construct()
    {
        $this->repo = new LocationRepository();
    }

    public function getAll(): array
    {
        return $this->repo->findAll();
    }

    public function getById(int $id): array|null
    {
        return $this->repo->findById($id);
    }

    public function getContractors(): array
    {
        return (new QueryBuilder())->table('contractors')->orderBy('full_name', 'ASC')->get();
    }

    public function create(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $type = $data['type'] ?? 'warehouse';

        if ($name === '') {
            return ['success' => false, 'errors' => ['name' => ['Name is required.']]];
        }
        if (!in_array($type, ['warehouse', 'vehicle', 'tech'], true)) {
            $type = 'warehouse';
        }

        $id = $this->repo->create([
            'name'          => $name,
            'type'          => $type,
            'contractor_id' => !empty($data['contractor_id']) ? (int) $data['contractor_id'] : null,
        ]);

        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        $name = trim($data['name'] ?? '');
        $type = $data['type'] ?? 'warehouse';

        if ($name === '') {
            return ['success' => false, 'errors' => ['name' => ['Name is required.']]];
        }
        if (!in_array($type, ['warehouse', 'vehicle', 'tech'], true)) {
            $type = 'warehouse';
        }

        $this->repo->update($id, [
            'name'          => $name,
            'type'          => $type,
            'contractor_id' => !empty($data['contractor_id']) ? (int) $data['contractor_id'] : null,
        ]);

        return ['success' => true];
    }

    public function delete(int $id): bool
    {
        if ($id === 1) {
            return false;
        }
        try {
            return $this->repo->delete($id);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
