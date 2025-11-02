<?php
/**
 * CREODENT Integrated Web Operations System
 * Audit Log Repository
 *
 * Tracks all changes to cases and case items for accountability
 * and debugging concurrent edit issues
 */

declare(strict_types=1);

class AuditLogRepository {
    /**
     * Create audit log entry
     *
     * @param array $data
     * @return int
     */
    public function log(array $data): int {
        $sql = "INSERT INTO case_audit_logs
                (case_id, case_item_id, user_id, action, description, before_json, after_json)
                VALUES
                (:case_id, :case_item_id, :user_id, :action, :description, :before_json, :after_json)";

        $params = [
            'case_id' => $data['case_id'] ?? null,
            'case_item_id' => $data['case_item_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'action' => $data['action'],
            'description' => $data['description'] ?? null,
            'before_json' => isset($data['before']) ? json_encode($data['before'], JSON_UNESCAPED_UNICODE) : null,
            'after_json' => isset($data['after']) ? json_encode($data['after'], JSON_UNESCAPED_UNICODE) : null,
        ];

        return Database::insert($sql, $params);
    }

    /**
     * Get audit logs for a case
     *
     * @param int $caseId
     * @param int $limit
     * @return array
     */
    public function getByCaseId(int $caseId, int $limit = 50): array {
        $sql = "SELECT al.*,
                       u.full_name AS user_name
                FROM case_audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.case_id = :case_id
                ORDER BY al.created_at DESC
                LIMIT :limit";

        return Database::fetchAll($sql, [
            'case_id' => $caseId,
            'limit' => $limit,
        ]);
    }

    /**
     * Get audit logs for a case item
     *
     * @param int $caseItemId
     * @param int $limit
     * @return array
     */
    public function getByCaseItemId(int $caseItemId, int $limit = 50): array {
        $sql = "SELECT al.*,
                       u.full_name AS user_name
                FROM case_audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.case_item_id = :case_item_id
                ORDER BY al.created_at DESC
                LIMIT :limit";

        return Database::fetchAll($sql, [
            'case_item_id' => $caseItemId,
            'limit' => $limit,
        ]);
    }

    /**
     * Get recent audit logs
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 100): array {
        $sql = "SELECT al.*,
                       u.full_name AS user_name,
                       c.external_case_no
                FROM case_audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN cases c ON al.case_id = c.id
                ORDER BY al.created_at DESC
                LIMIT :limit";

        return Database::fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Get audit logs by user
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getByUserId(int $userId, int $limit = 100): array {
        $sql = "SELECT al.*,
                       c.external_case_no
                FROM case_audit_logs al
                LEFT JOIN cases c ON al.case_id = c.id
                WHERE al.user_id = :user_id
                ORDER BY al.created_at DESC
                LIMIT :limit";

        return Database::fetchAll($sql, [
            'user_id' => $userId,
            'limit' => $limit,
        ]);
    }

    /**
     * Get audit logs by action type
     *
     * @param string $action
     * @param int $limit
     * @return array
     */
    public function getByAction(string $action, int $limit = 100): array {
        $sql = "SELECT al.*,
                       u.full_name AS user_name,
                       c.external_case_no
                FROM case_audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN cases c ON al.case_id = c.id
                WHERE al.action = :action
                ORDER BY al.created_at DESC
                LIMIT :limit";

        return Database::fetchAll($sql, [
            'action' => $action,
            'limit' => $limit,
        ]);
    }
}
