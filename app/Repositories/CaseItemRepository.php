<?php
/**
 * CREODENT Integrated Web Operations System
 * Case Item Repository - Database operations for case items
 *
 * Case items represent department-specific tasks within a case.
 * Supports optimistic locking like cases.
 */

declare(strict_types=1);

class CaseItemRepository {
    /**
     * Find case item by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array {
        $sql = "SELECT ci.*,
                       d.name AS department_name,
                       d.code AS department_code,
                       u.full_name AS assigned_to_name
                FROM case_items ci
                LEFT JOIN departments d ON ci.department_id = d.id
                LEFT JOIN users u ON ci.assigned_to = u.id
                WHERE ci.id = :id";

        return Database::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Get items by case ID
     *
     * @param int $caseId
     * @return array
     */
    public function getByCaseId(int $caseId): array {
        $sql = "SELECT ci.*,
                       d.name AS department_name,
                       d.code AS department_code,
                       u.full_name AS assigned_to_name
                FROM case_items ci
                LEFT JOIN departments d ON ci.department_id = d.id
                LEFT JOIN users u ON ci.assigned_to = u.id
                WHERE ci.case_id = :case_id
                ORDER BY ci.created_at ASC";

        return Database::fetchAll($sql, ['case_id' => $caseId]);
    }

    /**
     * Create a new case item
     *
     * @param array $data
     * @return int Item ID
     */
    public function create(array $data): int {
        $sql = "INSERT INTO case_items
                (case_id, department_id, work_type, tooth_no, count, instruction, preferences, assigned_to, status, version)
                VALUES
                (:case_id, :department_id, :work_type, :tooth_no, :count, :instruction, :preferences, :assigned_to, :status, 1)";

        $params = [
            'case_id' => $data['case_id'],
            'department_id' => $data['department_id'],
            'work_type' => $data['work_type'],
            'tooth_no' => $data['tooth_no'] ?? null,
            'count' => $data['count'] ?? 1,
            'instruction' => $data['instruction'] ?? null,
            'preferences' => $data['preferences'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ];

        return Database::insert($sql, $params);
    }

    /**
     * Update case item with optimistic locking
     *
     * @param int $id
     * @param array $data
     * @param int $currentVersion
     * @return bool
     */
    public function update(int $id, array $data, int $currentVersion): bool {
        $fields = [];
        $params = ['id' => $id, 'current_version' => $currentVersion];

        $allowedFields = ['tooth_no', 'count', 'instruction', 'preferences', 'assigned_to', 'status'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        // Handle timestamps for status changes
        if (isset($data['status'])) {
            if ($data['status'] === 'assigned' && !isset($data['assigned_at'])) {
                $fields[] = "assigned_at = NOW()";
            }
            if ($data['status'] === 'done' && !isset($data['completed_at'])) {
                $fields[] = "completed_at = NOW()";
            }
        }

        $fields[] = "version = version + 1";

        $sql = "UPDATE case_items
                SET " . implode(', ', $fields) . "
                WHERE id = :id AND version = :current_version";

        $affectedRows = Database::update($sql, $params);

        return $affectedRows > 0;
    }

    /**
     * Assign case item to worker
     *
     * @param int $id
     * @param int $userId
     * @param int $currentVersion
     * @return bool
     */
    public function assign(int $id, int $userId, int $currentVersion): bool {
        return $this->update($id, [
            'assigned_to' => $userId,
            'status' => 'assigned',
        ], $currentVersion);
    }

    /**
     * Get items assigned to a user
     *
     * @param int $userId
     * @param string|null $status
     * @return array
     */
    public function getByAssignedUser(int $userId, ?string $status = null): array {
        $sql = "SELECT ci.*,
                       c.external_case_no,
                       c.patient_name,
                       c.lab_name,
                       d.name AS department_name
                FROM case_items ci
                JOIN cases c ON ci.case_id = c.id
                LEFT JOIN departments d ON ci.department_id = d.id
                WHERE ci.assigned_to = :user_id";

        $params = ['user_id' => $userId];

        if ($status !== null) {
            $sql .= " AND ci.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY c.due_date ASC, ci.created_at ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get items by department
     *
     * @param int $departmentId
     * @param array $filters
     * @return array
     */
    public function getByDepartment(int $departmentId, array $filters = []): array {
        $sql = "SELECT ci.*,
                       c.external_case_no,
                       c.patient_name,
                       c.lab_name,
                       u.full_name AS assigned_to_name
                FROM case_items ci
                JOIN cases c ON ci.case_id = c.id
                LEFT JOIN users u ON ci.assigned_to = u.id
                WHERE ci.department_id = :department_id";

        $params = ['department_id' => $departmentId];

        if (!empty($filters['status'])) {
            $sql .= " AND ci.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['work_type'])) {
            $sql .= " AND ci.work_type = :work_type";
            $params['work_type'] = $filters['work_type'];
        }

        $sql .= " ORDER BY c.due_date ASC, ci.created_at ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Delete case item
     *
     * @param int $id
     * @return int
     */
    public function delete(int $id): int {
        $sql = "DELETE FROM case_items WHERE id = :id";
        return Database::delete($sql, ['id' => $id]);
    }
}
