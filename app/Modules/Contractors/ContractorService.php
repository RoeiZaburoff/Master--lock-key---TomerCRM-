<?php
declare(strict_types=1);

namespace App\Modules\Contractors;

use App\Core\Validator;

class ContractorService
{
    private ContractorRepository $repo;

    public function __construct()
    {
        $this->repo = new ContractorRepository();
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
        if (!$v->validate($data, ['full_name' => 'required|max:150', 'phone' => 'required|max:30', 'split_value' => 'required|numeric'])) {
            return ['success' => false, 'errors' => $v->errors()];
        }
        $id = $this->repo->create($this->sanitize($data));
        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        $v = new Validator();
        if (!$v->validate($data, ['full_name' => 'required|max:150', 'phone' => 'required|max:30', 'split_value' => 'required|numeric'])) {
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
            'full_name'   => trim($data['full_name']),
            'phone'       => trim($data['phone']),
            'email'       => trim($data['email'] ?? ''),
            'split_type'  => $data['split_type'] === 'percent' ? 'percent' : 'flat',
            'split_value' => (float) $data['split_value'],
            'is_active'   => isset($data['is_active']) ? 1 : 0,
        ];
    }
}
