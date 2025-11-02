<?php
/**
 * CREODENT Integrated Web Operations System
 * User Repository - Database operations for users
 */

declare(strict_types=1);

class UserRepository {
    /**
     * Find user by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array {
        $sql = "SELECT u.*, d.name AS department_name, d.code AS department_code
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE u.id = :id";

        return Database::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Find user by username
     *
     * @param string $username
     * @return array|null
     */
    public function findByUsername(string $username): ?array {
        $sql = "SELECT u.*, d.name AS department_name, d.code AS department_code
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE u.username = :username";

        return Database::fetchOne($sql, ['username' => $username]);
    }

    /**
     * Get all users with optional filters
     *
     * @param array $filters
     * @return array
     */
    public function getAll(array $filters = []): array {
        $sql = "SELECT u.*, d.name AS department_name, d.code AS department_code
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['department_id'])) {
            $sql .= " AND u.department_id = :department_id";
            $params['department_id'] = $filters['department_id'];
        }

        if (!empty($filters['role'])) {
            $sql .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND u.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY u.full_name ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create a new user
     *
     * @param array $data
     * @return int User ID
     */
    public function create(array $data): int {
        $sql = "INSERT INTO users (username, password_hash, full_name, email, department_id, role, status)
                VALUES (:username, :password_hash, :full_name, :email, :department_id, :role, :status)";

        $params = [
            'username' => $data['username'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'role' => $data['role'] ?? 'worker',
            'status' => $data['status'] ?? 'active',
        ];

        return Database::insert($sql, $params);
    }

    /**
     * Update user
     *
     * @param int $id
     * @param array $data
     * @return int Affected rows
     */
    public function update(int $id, array $data): int {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['full_name', 'email', 'department_id', 'role', 'status'])) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return 0;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";

        return Database::update($sql, $params);
    }

    /**
     * Update user password
     *
     * @param int $id
     * @param string $newPassword
     * @return int
     */
    public function updatePassword(int $id, string $newPassword): int {
        $sql = "UPDATE users SET password_hash = :password_hash WHERE id = :id";

        return Database::update($sql, [
            'id' => $id,
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
        ]);
    }

    /**
     * Update last login timestamp
     *
     * @param int $id
     * @return int
     */
    public function updateLastLogin(int $id): int {
        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = :id";

        return Database::update($sql, ['id' => $id]);
    }

    /**
     * Verify user credentials
     *
     * @param string $username
     * @param string $password
     * @return array|null User data if valid, null otherwise
     */
    public function verifyCredentials(string $username, string $password): ?array {
        $user = $this->findByUsername($username);

        if (!$user) {
            return null;
        }

        if ($user['status'] !== 'active') {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    /**
     * Check if username exists
     *
     * @param string $username
     * @param int|null $excludeId Exclude this user ID from check (for updates)
     * @return bool
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = :username";
        $params = ['username' => $username];

        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $result = Database::fetchOne($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Delete user (soft delete by setting status to inactive)
     *
     * @param int $id
     * @return int
     */
    public function delete(int $id): int {
        $sql = "UPDATE users SET status = 'inactive' WHERE id = :id";

        return Database::update($sql, ['id' => $id]);
    }

    /**
     * Get users by department
     *
     * @param int $departmentId
     * @return array
     */
    public function getByDepartment(int $departmentId): array {
        return $this->getAll(['department_id' => $departmentId, 'status' => 'active']);
    }

    /**
     * Get workers for assignment
     *
     * @param int|null $departmentId
     * @return array
     */
    public function getWorkersForAssignment(?int $departmentId = null): array {
        $sql = "SELECT u.id, u.username, u.full_name, d.name AS department_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE u.status = 'active' AND u.role IN ('worker', 'manager')";

        $params = [];

        if ($departmentId !== null) {
            $sql .= " AND u.department_id = :department_id";
            $params['department_id'] = $departmentId;
        }

        $sql .= " ORDER BY u.full_name ASC";

        return Database::fetchAll($sql, $params);
    }
}
