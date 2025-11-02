<?php
/**
 * Case Repository
 * Handles all database operations for cases
 */

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CaseRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find case by ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM cases WHERE id = ?',
            [$id]
        );
    }

    /**
     * Find case by external case number
     */
    public function findByExternalCaseNo(string $caseNo): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM cases WHERE external_case_no = ?',
            [$caseNo]
        );
    }

    /**
     * Get all cases with pagination and filters
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $conditions = [];
        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['location'])) {
            $conditions[] = 'location = ?';
            $params[] = $filters['location'];
        }

        if (!empty($filters['lab_name'])) {
            $conditions[] = 'lab_name LIKE ?';
            $params[] = '%' . $filters['lab_name'] . '%';
        }

        if (!empty($filters['patient_name'])) {
            $conditions[] = 'patient_name LIKE ?';
            $params[] = '%' . $filters['patient_name'] . '%';
        }

        if (!empty($filters['case_no'])) {
            $conditions[] = 'external_case_no LIKE ?';
            $params[] = '%' . $filters['case_no'] . '%';
        }

        if (!empty($filters['due_date_from'])) {
            $conditions[] = 'due_date >= ?';
            $params[] = $filters['due_date_from'];
        }

        if (!empty($filters['due_date_to'])) {
            $conditions[] = 'due_date <= ?';
            $params[] = $filters['due_date_to'];
        }

        // Build WHERE clause
        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Count total
        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM cases $where",
            $params
        );

        // Get paginated results
        $offset = ($page - 1) * $perPage;

        $cases = $this->db->fetchAll(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM case_items WHERE case_id = c.id) as total_items,
                    (SELECT COUNT(*) FROM case_items WHERE case_id = c.id AND status = 'done') as completed_items
             FROM cases c
             $where
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return [
            'data' => $cases,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Create new case
     */
    public function create(array $data): int
    {
        return $this->db->insert('cases', [
            'external_case_no' => $data['external_case_no'],
            'source' => $data['source'] ?? 'manual',
            'patient_name' => $data['patient_name'] ?? null,
            'lab_name' => $data['lab_name'] ?? null,
            'account_name' => $data['account_name'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'location' => $data['location'] ?? 'HV',
            'status' => $data['status'] ?? 'new',
            'raw_payload' => $data['raw_payload'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update case with optimistic locking
     */
    public function update(int $id, array $data, int $currentVersion): bool
    {
        $data['version'] = $currentVersion + 1;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $rowsAffected = $this->db->update(
            'cases',
            $data,
            'id = :id AND version = :current_version',
            ['id' => $id, 'current_version' => $currentVersion]
        );

        if ($rowsAffected === 0) {
            throw new \RuntimeException('Case was modified by another user. Please refresh and try again.');
        }

        return true;
    }

    /**
     * Update case status
     */
    public function updateStatus(int $id, string $status, int $currentVersion): bool
    {
        return $this->update($id, ['status' => $status], $currentVersion);
    }

    /**
     * Delete case (soft delete by status)
     */
    public function delete(int $id): bool
    {
        return $this->db->update(
            'cases',
            ['status' => 'archived', 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $id]
        ) > 0;
    }

    /**
     * Upsert case (insert or update if exists)
     */
    public function upsert(array $data): int
    {
        $existing = $this->findByExternalCaseNo($data['external_case_no']);

        if ($existing) {
            $this->update($existing['id'], $data, $existing['version']);
            return $existing['id'];
        }

        return $this->create($data);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare('CALL get_dashboard_stats()');
        $stmt->execute();

        return $stmt->fetch() ?: [];
    }

    /**
     * Get department workload
     */
    public function getDepartmentWorkload(): array
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare('CALL get_department_workload()');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get case overview with department info
     */
    public function getCaseOverview(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM view_case_overview WHERE id = ?',
            [$id]
        );
    }

    /**
     * Search cases (full-text search)
     */
    public function search(string $query, int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;

        $cases = $this->db->fetchAll(
            "SELECT * FROM cases
             WHERE MATCH(patient_name, lab_name, account_name) AGAINST(? IN NATURAL LANGUAGE MODE)
                OR external_case_no LIKE ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$query, "%$query%", $perPage, $offset]
        );

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM cases
             WHERE MATCH(patient_name, lab_name, account_name) AGAINST(? IN NATURAL LANGUAGE MODE)
                OR external_case_no LIKE ?",
            [$query, "%$query%"]
        );

        return [
            'data' => $cases,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get cases due today
     */
    public function getDueToday(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM cases
             WHERE due_date = CURDATE()
               AND status NOT IN ('done', 'canceled', 'archived')
             ORDER BY created_at ASC"
        );
    }

    /**
     * Get overdue cases
     */
    public function getOverdue(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM cases
             WHERE due_date < CURDATE()
               AND status NOT IN ('done', 'canceled', 'archived')
             ORDER BY due_date ASC"
        );
    }

    /**
     * Get recent cases
     */
    public function getRecent(int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM cases ORDER BY created_at DESC LIMIT ?',
            [$limit]
        );
    }
}
