<?php
declare(strict_types=1);

namespace App\Modules\LeadSources;

use App\Contracts\RepositoryInterface;
use App\Core\QueryBuilder;

class LeadSourceRepository implements RepositoryInterface
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function findById(int $id): array|null
    {
        return $this->db->table('lead_sources')->where('lead_source_id', $id)->first();
    }

    public function findAll(array $filters = []): array
    {
        return $this->db->table('lead_sources')->orderBy('created_at', 'DESC')->get();
    }

    public function create(array $data): int
    {
        return $this->db->table('lead_sources')->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table('lead_sources')->where('lead_source_id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->table('lead_sources')->where('lead_source_id', $id)->delete();
    }
}
