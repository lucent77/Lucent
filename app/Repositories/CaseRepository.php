<?php
/**
 * CREODENT Integrated Web Operations System
 * Case Repository - Database operations for cases
 *
 * IMPORTANT: This repository implements optimistic locking to prevent
 * concurrent update conflicts when multiple users edit the same case.
 */

declare(strict_types=1);

class CaseRepository {
    /**
     * Find case by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array {
        $sql = "SELECT * FROM cases WHERE id = :id";
        return Database::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Find case by external case number
     *
     * @param string $externalCaseNo
     * @return array|null
     */
    public function findByExternalCaseNo(string $externalCaseNo): ?array {
        $sql = "SELECT * FROM cases WHERE external_case_no = :external_case_no";
        return Database::fetchOne($sql, ['external_case_no' => $externalCaseNo]);
    }

    /**
     * Get cases with pagination and filters
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array ['data' => [], 'total' => int, 'pages' => int]
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 25): array {
        $offset = ($page - 1) * $perPage;

        // Build WHERE clause
        $where = $this->buildWhereClause($filters);

        // Count total records
        $countSql = "SELECT COUNT(*) as total FROM cases c WHERE " . $where['clause'];
        $totalResult = Database::fetchOne($countSql, $where['params']);
        $total = $totalResult['total'];

        // Fetch paginated data
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM case_items WHERE case_id = c.id) as items_count,
                       (SELECT COUNT(*) FROM case_items WHERE case_id = c.id AND status = 'done') as completed_items
                FROM cases c
                WHERE " . $where['clause'] . "
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset";

        $params = array_merge($where['params'], [
            'limit' => $perPage,
            'offset' => $offset,
        ]);

        $data = Database::fetchAll($sql, $params);

        return [
            'data' => $data,
            'total' => $total,
            'pages' => ceil($total / $perPage),
            'current_page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Build WHERE clause from filters
     *
     * @param array $filters
     * @return array ['clause' => string, 'params' => array]
     */
    private function buildWhereClause(array $filters): array {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['case_number'])) {
            $conditions[] = "c.external_case_no LIKE :case_number";
            $params['case_number'] = '%' . $filters['case_number'] . '%';
        }

        if (!empty($filters['patient_name'])) {
            $conditions[] = "c.patient_name LIKE :patient_name";
            $params['patient_name'] = '%' . $filters['patient_name'] . '%';
        }

        if (!empty($filters['lab_name'])) {
            $conditions[] = "c.lab_name LIKE :lab_name";
            $params['lab_name'] = '%' . $filters['lab_name'] . '%';
        }

        if (!empty($filters['status'])) {
            $conditions[] = "c.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['source'])) {
            $conditions[] = "c.source = :source";
            $params['source'] = $filters['source'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "c.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "c.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        return [
            'clause' => implode(' AND ', $conditions),
            'params' => $params,
        ];
    }

    /**
     * Create a new case
     *
     * @param array $data
     * @return int Case ID
     */
    public function create(array $data): int {
        $sql = "INSERT INTO cases
                (external_case_no, source, patient_name, lab_name, location, due_date, status, raw_payload, version)
                VALUES
                (:external_case_no, :source, :patient_name, :lab_name, :location, :due_date, :status, :raw_payload, 1)";

        $params = [
            'external_case_no' => $data['external_case_no'],
            'source' => $data['source'] ?? 'manual',
            'patient_name' => $data['patient_name'] ?? null,
            'lab_name' => $data['lab_name'] ?? null,
            'location' => $data['location'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => $data['status'] ?? 'new',
            'raw_payload' => $data['raw_payload'] ?? null,
        ];

        return Database::insert($sql, $params);
    }

    /**
     * Update case with optimistic locking
     *
     * IMPORTANT: This method implements optimistic locking to prevent
     * concurrent update conflicts. It checks the version number before
     * updating and returns false if the version has changed.
     *
     * @param int $id
     * @param array $data
     * @param int $currentVersion
     * @return bool True if updated successfully, false if version conflict
     */
    public function update(int $id, array $data, int $currentVersion): bool {
        $fields = [];
        $params = ['id' => $id, 'current_version' => $currentVersion];

        // Allowed fields for update
        $allowedFields = ['patient_name', 'lab_name', 'location', 'due_date', 'status', 'raw_payload'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        // Add version increment to fields
        $fields[] = "version = version + 1";

        // Build SQL with optimistic locking WHERE clause
        $sql = "UPDATE cases
                SET " . implode(', ', $fields) . "
                WHERE id = :id AND version = :current_version";

        $affectedRows = Database::update($sql, $params);

        // If no rows affected, version conflict occurred
        return $affectedRows > 0;
    }

    /**
     * Upsert case (insert or update based on external_case_no)
     *
     * @param array $data
     * @return int Case ID
     */
    public function upsert(array $data): int {
        $existing = $this->findByExternalCaseNo($data['external_case_no']);

        if ($existing) {
            // Update existing case
            $this->update($existing['id'], $data, $existing['version']);
            return $existing['id'];
        } else {
            // Create new case
            return $this->create($data);
        }
    }

    /**
     * Get case with all items
     *
     * @param int $id
     * @return array|null
     */
    public function findWithItems(int $id): ?array {
        $case = $this->findById($id);

        if (!$case) {
            return null;
        }

        // Get case items
        $sql = "SELECT ci.*,
                       d.name AS department_name,
                       d.code AS department_code,
                       u.full_name AS assigned_to_name
                FROM case_items ci
                LEFT JOIN departments d ON ci.department_id = d.id
                LEFT JOIN users u ON ci.assigned_to = u.id
                WHERE ci.case_id = :case_id
                ORDER BY ci.created_at ASC";

        $case['items'] = Database::fetchAll($sql, ['case_id' => $id]);

        return $case;
    }

    /**
     * Get dashboard statistics
     *
     * IMPORTANT: Date filtering is enforced to prevent infinite data loading.
     * Always use date ranges when querying for statistics.
     *
     * @param string $dateFrom Format: YYYY-MM-DD
     * @param string $dateTo Format: YYYY-MM-DD
     * @return array
     */
    public function getDashboardStats(string $dateFrom, string $dateTo): array {
        // Total cases by status
        $sql = "SELECT status, COUNT(*) as count
                FROM cases
                WHERE created_at BETWEEN :date_from AND :date_to
                GROUP BY status";

        $params = [
            'date_from' => $dateFrom . ' 00:00:00',
            'date_to' => $dateTo . ' 23:59:59',
        ];

        $statusCounts = Database::fetchAll($sql, $params);

        // Cases by department (via case_items)
        $sql = "SELECT d.code, d.name, COUNT(DISTINCT ci.case_id) as case_count
                FROM case_items ci
                JOIN departments d ON ci.department_id = d.id
                JOIN cases c ON ci.case_id = c.id
                WHERE c.created_at BETWEEN :date_from AND :date_to
                GROUP BY d.id, d.code, d.name";

        $departmentCounts = Database::fetchAll($sql, $params);

        // Cases per day (for chart - limited to prevent infinite data)
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                FROM cases
                WHERE created_at BETWEEN :date_from AND :date_to
                GROUP BY DATE(created_at)
                ORDER BY date DESC
                LIMIT 90";

        $dailyCounts = Database::fetchAll($sql, $params);

        return [
            'status_counts' => $statusCounts,
            'department_counts' => $departmentCounts,
            'daily_counts' => $dailyCounts,
        ];
    }

    /**
     * Delete case (soft delete by setting status to canceled)
     *
     * @param int $id
     * @return int
     */
    public function delete(int $id): int {
        $sql = "UPDATE cases SET status = 'canceled' WHERE id = :id";
        return Database::update($sql, ['id' => $id]);
    }

    /**
     * Get recent cases
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 10): array {
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM case_items WHERE case_id = c.id) as items_count
                FROM cases c
                WHERE c.status != 'canceled'
                ORDER BY c.created_at DESC
                LIMIT :limit";

        return Database::fetchAll($sql, ['limit' => $limit]);
    }
}
