<?php
declare(strict_types=1);

namespace App\Modules\Customers;

use App\Core\Validator;

class CustomerService
{
    private CustomerRepository $repo;

    public function __construct()
    {
        $this->repo = new CustomerRepository();
    }

    public function getAll(array $filters = []): array
    {
        return $this->repo->findAll($filters);
    }

    public function getById(int $id): array|null
    {
        return $this->repo->findById($id);
    }

    public function getWithHistory(int $id): array|null
    {
        $customer = $this->repo->findById($id);
        if ($customer === null) {
            return null;
        }
        $customer['jobs'] = $this->repo->getJobHistory($id);
        return $customer;
    }

    public function create(array $data): array
    {
        $v = new Validator();
        if (!$v->validate($data, $this->rules())) {
            return ['success' => false, 'errors' => $v->errors()];
        }
        $id = $this->repo->create($this->sanitize($data));
        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        $v = new Validator();
        if (!$v->validate($data, $this->rules())) {
            return ['success' => false, 'errors' => $v->errors()];
        }
        $this->repo->update($id, $this->sanitize($data));
        return ['success' => true];
    }

    public function findByPhone(string $phone): array|null
    {
        return $this->repo->findByPhone($phone);
    }

    public function findByEmail(string $email): array|null
    {
        return $this->repo->findByEmail($email);
    }

    public function findByAddress(string $address): array|null
    {
        return $this->repo->findByAddress($address);
    }

    public function delete(int $id): array
    {
        $jobCount = $this->repo->countJobs($id);
        if ($jobCount > 0) {
            return [
                'success' => false,
                'message' => "Cannot delete: this customer has {$jobCount} associated lead(s). Delete or reassign them first.",
            ];
        }
        $this->repo->delete($id);
        return ['success' => true];
    }

    private function rules(): array
    {
        return [
            'full_name'     => 'required|max:150',
            'phone'         => 'required|max:30',
            'address_line1' => 'required|max:255',
            'city'          => 'required|max:100',
            'state'         => 'required|max:50',
            'zip'           => 'required|max:20',
        ];
    }

    private function sanitize(array $data): array
    {
        return [
            'full_name'     => trim($data['full_name']),
            'phone'         => trim($data['phone']),
            'email'         => trim($data['email'] ?? ''),
            'address_line1' => trim($data['address_line1']),
            'address_line2' => trim($data['address_line2'] ?? ''),
            'city'          => trim($data['city']),
            'state'         => trim($data['state']),
            'zip'           => trim($data['zip']),
            'notes'         => trim($data['notes'] ?? ''),
        ];
    }
}
