<?php
/**
 * Audit Log Repository
 */

namespace App\Repositories;

use App\Core\Database;

class AuditLogRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create audit log entry
     */
    public function log(
        ?int $caseId,
        ?int $userId,
        string $action,
        string $description,
        ?array $beforeData = null,
        ?array $afterData = null
    ): int {
        return $this->db->insert('case_audit_logs', [
            'case_id' => $caseId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'before_json' => $beforeData ? json_encode($beforeData) : null,
            'after_json' => $afterData ? json_encode($afterData) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get logs for case
     */
    public function getByCaseId(int $caseId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            'SELECT al.*, u.name as user_name, u.username
             FROM case_audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE al.case_id = ?
             ORDER BY al.created_at DESC
             LIMIT ?',
            [$caseId, $limit]
        );
    }

    /**
     * Get logs for user
     */
    public function getByUserId(int $userId, int $limit = 100): array
    {
        return $this->db->fetchAll(
            'SELECT al.*, c.external_case_no
             FROM case_audit_logs al
             LEFT JOIN cases c ON al.case_id = c.id
             WHERE al.user_id = ?
             ORDER BY al.created_at DESC
             LIMIT ?',
            [$userId, $limit]
        );
    }

    /**
     * Get recent logs
     */
    public function getRecent(int $limit = 100): array
    {
        return $this->db->fetchAll(
            'SELECT al.*, u.name as user_name, c.external_case_no
             FROM case_audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             LEFT JOIN cases c ON al.case_id = c.id
             ORDER BY al.created_at DESC
             LIMIT ?',
            [$limit]
        );
    }
}
