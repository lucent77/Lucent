<?php
/**
 * Case Item Repository
 * Handles database operations for case items (work items per department)
 */

namespace App\Repositories;

use App\Core\Database;

class CaseItemRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find item by ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT ci.*, d.name as department_name, u.name as assigned_user_name
             FROM case_items ci
             LEFT JOIN departments d ON ci.department_id = d.id
             LEFT JOIN users u ON ci.assigned_to = u.id
             WHERE ci.id = ?',
            [$id]
        );
    }

    /**
     * Get all items for a case
     */
    public function getByCaseId(int $caseId): array
    {
        return $this->db->fetchAll(
            'SELECT ci.*, d.name as department_name, d.code as department_code,
                    u.name as assigned_user_name
             FROM case_items ci
             LEFT JOIN departments d ON ci.department_id = d.id
             LEFT JOIN users u ON ci.assigned_to = u.id
             WHERE ci.case_id = ?
             ORDER BY ci.created_at ASC',
            [$caseId]
        );
    }

    /**
     * Get items by department
     */
    public function getByDepartment(int $departmentId, array $filters = []): array
    {
        $conditions = ['ci.department_id = ?'];
        $params = [$departmentId];

        if (!empty($filters['status'])) {
            $conditions[] = 'ci.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['assigned_to'])) {
            $conditions[] = 'ci.assigned_to = ?';
            $params[] = $filters['assigned_to'];
        }

        $where = implode(' AND ', $conditions);

        return $this->db->fetchAll(
            "SELECT ci.*, c.external_case_no, c.patient_name, c.lab_name, c.due_date,
                    u.name as assigned_user_name
             FROM case_items ci
             INNER JOIN cases c ON ci.case_id = c.id
             LEFT JOIN users u ON ci.assigned_to = u.id
             WHERE $where
             ORDER BY c.due_date ASC, ci.created_at ASC",
            $params
        );
    }

    /**
     * Get items assigned to user
     */
    public function getByAssignedUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT ci.*, c.external_case_no, c.patient_name, c.lab_name, c.due_date,
                    d.name as department_name
             FROM case_items ci
             INNER JOIN cases c ON ci.case_id = c.id
             LEFT JOIN departments d ON ci.department_id = d.id
             WHERE ci.assigned_to = ?
               AND ci.status NOT IN ("done", "rejected")
             ORDER BY c.due_date ASC',
            [$userId]
        );
    }

    /**
     * Create case item
     */
    public function create(array $data): int
    {
        return $this->db->insert('case_items', [
            'case_id' => $data['case_id'],
            'department_id' => $data['department_id'],
            'work_type' => $data['work_type'],
            'tooth_no' => $data['tooth_no'] ?? null,
            'part_no' => $data['part_no'] ?? null,
            'count' => $data['count'] ?? 1,
            'case_type' => $data['case_type'] ?? null,
            'color' => $data['color'] ?? null,
            'instruction' => $data['instruction'] ?? null,
            'preferences' => $data['preferences'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update case item with optimistic locking
     */
    public function update(int $id, array $data, int $currentVersion): bool
    {
        $data['version'] = $currentVersion + 1;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $rowsAffected = $this->db->update(
            'case_items',
            $data,
            'id = :id AND version = :current_version',
            ['id' => $id, 'current_version' => $currentVersion]
        );

        if ($rowsAffected === 0) {
            throw new \RuntimeException('Item was modified by another user. Please refresh and try again.');
        }

        return true;
    }

    /**
     * Assign item to user
     */
    public function assign(int $id, int $userId, int $currentVersion): bool
    {
        return $this->update($id, [
            'assigned_to' => $userId,
            'assigned_at' => date('Y-m-d H:i:s'),
            'status' => 'assigned'
        ], $currentVersion);
    }

    /**
     * Update item status
     */
    public function updateStatus(int $id, string $status, int $currentVersion): bool
    {
        $data = ['status' => $status];

        // Set timestamp based on status
        if ($status === 'working') {
            $data['started_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'done') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        return $this->update($id, $data, $currentVersion);
    }

    /**
     * Bulk create items
     */
    public function bulkCreate(array $items): array
    {
        $ids = [];

        $this->db->beginTransaction();

        try {
            foreach ($items as $item) {
                $ids[] = $this->create($item);
            }

            $this->db->commit();

            return $ids;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Delete item
     */
    public function delete(int $id): bool
    {
        return $this->db->delete('case_items', 'id = ?', [$id]) > 0;
    }

    /**
     * Get pending items count by department
     */
    public function getPendingCountByDepartment(int $departmentId): int
    {
        return (int)$this->db->fetchColumn(
            'SELECT COUNT(*) FROM case_items WHERE department_id = ? AND status = ?',
            [$departmentId, 'pending']
        );
    }

    /**
     * Get workload statistics for user
     */
    public function getUserWorkload(int $userId): array
    {
        $result = $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_assigned,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN status = 'working' THEN 1 ELSE 0 END) as working,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done,
                SUM(CASE WHEN status = 'remake' THEN 1 ELSE 0 END) as remake
             FROM case_items
             WHERE assigned_to = ?",
            [$userId]
        );

        return $result ?: [];
    }
}
