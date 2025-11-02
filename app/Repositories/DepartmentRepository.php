<?php
/**
 * CREODENT Integrated Web Operations System
 * Department Repository
 */

declare(strict_types=1);

class DepartmentRepository {
    /**
     * Find department by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array {
        $sql = "SELECT * FROM departments WHERE id = :id";
        return Database::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Find department by code
     *
     * @param string $code
     * @return array|null
     */
    public function findByCode(string $code): ?array {
        $sql = "SELECT * FROM departments WHERE code = :code";
        return Database::fetchOne($sql, ['code' => $code]);
    }

    /**
     * Get all departments
     *
     * @param bool $activeOnly
     * @return array
     */
    public function getAll(bool $activeOnly = true): array {
        $sql = "SELECT * FROM departments";

        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }

        $sql .= " ORDER BY name ASC";

        return Database::fetchAll($sql);
    }

    /**
     * Create department
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int {
        $sql = "INSERT INTO departments (code, name, description, is_active)
                VALUES (:code, :name, :description, :is_active)";

        $params = [
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
        ];

        return Database::insert($sql, $params);
    }

    /**
     * Update department
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update(int $id, array $data): int {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'description', 'is_active'])) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return 0;
        }

        $sql = "UPDATE departments SET " . implode(', ', $fields) . " WHERE id = :id";

        return Database::update($sql, $params);
    }

    /**
     * Delete department
     *
     * @param int $id
     * @return int
     */
    public function delete(int $id): int {
        $sql = "UPDATE departments SET is_active = 0 WHERE id = :id";
        return Database::update($sql, ['id' => $id]);
    }
}
