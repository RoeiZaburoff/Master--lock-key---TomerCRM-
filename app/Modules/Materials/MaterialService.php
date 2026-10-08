<?php
declare(strict_types=1);

namespace App\Modules\Materials;

use App\Core\Validator;

class MaterialService
{
    private MaterialRepository $repo;

    public function __construct()
    {
        $this->repo = new MaterialRepository();
    }

    public function getAll(): array
    {
        return $this->repo->findAll();
    }

    public function getById(int $id): array|null
    {
        return $this->repo->findById($id);
    }

    public function create(array $data): array
    {
        $v = new Validator();
        if (!$v->validate($data, ['name' => 'required|max:150', 'unit_cost' => 'required|numeric'])) {
            return ['success' => false, 'errors' => $v->errors()];
        }
        $id = $this->repo->create($this->sanitize($data));
        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        $v = new Validator();
        if (!$v->validate($data, ['name' => 'required|max:150', 'unit_cost' => 'required|numeric'])) {
            return ['success' => false, 'errors' => $v->errors()];
        }
        $this->repo->update($id, $this->sanitize($data));
        return ['success' => true];
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }

    private function sanitize(array $data): array
    {
        return [
            'name'      => trim($data['name']),
            'unit'      => trim($data['unit'] ?? 'each'),
            'unit_cost' => (float) $data['unit_cost'],
            'stock_qty' => (float) ($data['stock_qty'] ?? 0),
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ];
    }
}
