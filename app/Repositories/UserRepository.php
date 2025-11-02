<?php
/**
 * User Repository
 */

namespace App\Repositories;

use App\Core\Database;

class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT u.*, d.name as department_name, d.code as department_code
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.id = ?',
            [$id]
        );
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE username = ?',
            [$username]
        );
    }

    public function getAll(array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'u.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['role'])) {
            $conditions[] = 'u.role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['department_id'])) {
            $conditions[] = 'u.department_id = ?';
            $params[] = $filters['department_id'];
        }

        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return $this->db->fetchAll(
            "SELECT u.*, d.name as department_name
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             $where
             ORDER BY u.name ASC",
            $params
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert('users', [
            'username' => $data['username'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'role' => $data['role'] ?? 'worker',
            'status' => $data['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Don't update password_hash if not provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return $this->db->update('users', $data, 'id = :id', ['id' => $id]) > 0;
    }

    public function delete(int $id): bool
    {
        // Soft delete by setting status to inactive
        return $this->update($id, ['status' => 'inactive']);
    }

    public function getByDepartment(int $departmentId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM users WHERE department_id = ? AND status = ? ORDER BY name ASC',
            [$departmentId, 'active']
        );
    }

    public function getByRole(string $role): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM users WHERE role = ? AND status = ? ORDER BY name ASC',
            [$role, 'active']
        );
    }
}
