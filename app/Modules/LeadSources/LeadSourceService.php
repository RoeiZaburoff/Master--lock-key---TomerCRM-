<?php
declare(strict_types=1);

namespace App\Modules\LeadSources;

use App\Core\Validator;

class LeadSourceService
{
    private LeadSourceRepository $repo;

    public function __construct()
    {
        $this->repo = new LeadSourceRepository();
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
        if (!$v->validate($data, ['name' => 'required|max:100', 'fee_value' => 'required|numeric'])) {
            return ['success' => false, 'errors' => $v->errors()];
        }
        $id = $this->repo->create($this->sanitize($data));
        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        $v = new Validator();
        if (!$v->validate($data, ['name' => 'required|max:100', 'fee_value' => 'required|numeric'])) {
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
            'fee_type'  => $data['fee_type'] === 'percent' ? 'percent' : 'flat',
            'fee_value' => (float) $data['fee_value'],
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ];
    }
}
