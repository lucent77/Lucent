<?php
/**
 * Department Repository
 */

namespace App\Repositories;

use App\Core\Database;

class DepartmentRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM departments WHERE id = ?',
            [$id]
        );
    }

    public function findByCode(string $code): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM departments WHERE code = ?',
            [$code]
        );
    }

    public function getAll(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM departments';

        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }

        $sql .= ' ORDER BY name ASC';

        return $this->db->fetchAll($sql);
    }

    public function create(array $data): int
    {
        return $this->db->insert('departments', [
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('departments', $data, 'id = :id', ['id' => $id]) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->update($id, ['is_active' => 0]);
    }
}
