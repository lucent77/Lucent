<?php
/**
 * Case Service
 * Business logic for case management
 */

namespace App\Services;

use App\Repositories\CaseRepository;
use App\Repositories\CaseItemRepository;
use App\Repositories\AuditLogRepository;
use App\Core\Database;

class CaseService
{
    private CaseRepository $caseRepo;
    private CaseItemRepository $itemRepo;
    private AuditLogRepository $auditRepo;
    private Database $db;

    public function __construct()
    {
        $this->caseRepo = new CaseRepository();
        $this->itemRepo = new CaseItemRepository();
        $this->auditRepo = new AuditLogRepository();
        $this->db = Database::getInstance();
    }

    /**
     * Get case with all related items
     */
    public function getCaseWithItems(int $id): ?array
    {
        $case = $this->caseRepo->find($id);

        if (!$case) {
            return null;
        }

        $case['items'] = $this->itemRepo->getByCaseId($id);
        $case['audit_logs'] = $this->auditRepo->getByCaseId($id, 20);

        return $case;
    }

    /**
     * Create new case with items
     */
    public function createCase(array $caseData, array $items = [], ?int $userId = null): int
    {
        $this->db->beginTransaction();

        try {
            // Create case
            $caseId = $this->caseRepo->create($caseData);

            // Create items if provided
            if (!empty($items)) {
                foreach ($items as $item) {
                    $item['case_id'] = $caseId;
                    $this->itemRepo->create($item);
                }
            }

            // Log creation
            $this->auditRepo->log(
                $caseId,
                $userId,
                'create',
                'Case created',
                null,
                $caseData
            );

            $this->db->commit();

            return $caseId;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update case with optimistic locking
     */
    public function updateCase(int $id, array $data, int $currentVersion, ?int $userId = null): bool
    {
        $before = $this->caseRepo->find($id);

        $this->caseRepo->update($id, $data, $currentVersion);

        $after = $this->caseRepo->find($id);

        // Log update
        $this->auditRepo->log(
            $id,
            $userId,
            'update',
            'Case updated',
            $before,
            $after
        );

        return true;
    }

    /**
     * Update case status
     */
    public function updateCaseStatus(int $id, string $status, int $currentVersion, ?int $userId = null): bool
    {
        $before = $this->caseRepo->find($id);

        $this->caseRepo->updateStatus($id, $status, $currentVersion);

        // Log status change
        $this->auditRepo->log(
            $id,
            $userId,
            'status_change',
            "Status changed from {$before['status']} to $status",
            ['status' => $before['status']],
            ['status' => $status]
        );

        return true;
    }

    /**
     * Assign case item to user
     */
    public function assignItem(int $itemId, int $userId, int $currentVersion, ?int $assignedBy = null): bool
    {
        $item = $this->itemRepo->find($itemId);

        if (!$item) {
            throw new \RuntimeException('Item not found');
        }

        $this->itemRepo->assign($itemId, $userId, $currentVersion);

        // Log assignment
        $this->auditRepo->log(
            $item['case_id'],
            $assignedBy,
            'assign',
            "Item assigned to user ID: $userId",
            ['assigned_to' => $item['assigned_to']],
            ['assigned_to' => $userId]
        );

        return true;
    }

    /**
     * Update item status
     */
    public function updateItemStatus(int $itemId, string $status, int $currentVersion, ?int $userId = null): bool
    {
        $item = $this->itemRepo->find($itemId);

        if (!$item) {
            throw new \RuntimeException('Item not found');
        }

        $this->itemRepo->updateStatus($itemId, $status, $currentVersion);

        // Log status change
        $this->auditRepo->log(
            $item['case_id'],
            $userId,
            'item_status_change',
            "Item status changed from {$item['status']} to $status",
            ['status' => $item['status']],
            ['status' => $status]
        );

        // Check if all items are done, update case status
        $this->checkAndUpdateCaseStatus($item['case_id'], $userId);

        return true;
    }

    /**
     * Check all items and update case status if needed
     */
    private function checkAndUpdateCaseStatus(int $caseId, ?int $userId): void
    {
        $items = $this->itemRepo->getByCaseId($caseId);

        if (empty($items)) {
            return;
        }

        $allDone = true;
        $anyWorking = false;

        foreach ($items as $item) {
            if ($item['status'] !== 'done') {
                $allDone = false;
            }
            if (in_array($item['status'], ['assigned', 'working'])) {
                $anyWorking = true;
            }
        }

        $case = $this->caseRepo->find($caseId);

        if ($allDone && $case['status'] !== 'done') {
            $this->caseRepo->updateStatus($caseId, 'done', $case['version']);

            $this->auditRepo->log(
                $caseId,
                $userId,
                'auto_status_change',
                'Case automatically marked as done (all items completed)'
            );
        } elseif ($anyWorking && $case['status'] === 'new') {
            $this->caseRepo->updateStatus($caseId, 'in_progress', $case['version']);

            $this->auditRepo->log(
                $caseId,
                $userId,
                'auto_status_change',
                'Case automatically marked as in progress'
            );
        }
    }

    /**
     * Delete case (soft delete)
     */
    public function deleteCase(int $id, ?int $userId = null): bool
    {
        $case = $this->caseRepo->find($id);

        if (!$case) {
            throw new \RuntimeException('Case not found');
        }

        $this->caseRepo->delete($id);

        $this->auditRepo->log(
            $id,
            $userId,
            'delete',
            'Case archived'
        );

        return true;
    }

    /**
     * Get dashboard data
     */
    public function getDashboardData(): array
    {
        return [
            'stats' => $this->caseRepo->getDashboardStats(),
            'department_workload' => $this->caseRepo->getDepartmentWorkload(),
            'due_today' => $this->caseRepo->getDueToday(),
            'overdue' => $this->caseRepo->getOverdue(),
            'recent_cases' => $this->caseRepo->getRecent(5)
        ];
    }

    /**
     * Get user's assigned work
     */
    public function getUserWork(int $userId): array
    {
        return [
            'assigned_items' => $this->itemRepo->getByAssignedUser($userId),
            'workload' => $this->itemRepo->getUserWorkload($userId)
        ];
    }

    /**
     * Bulk assign items to user
     */
    public function bulkAssignItems(array $itemIds, int $userId, ?int $assignedBy = null): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($itemIds as $itemId) {
            try {
                $item = $this->itemRepo->find($itemId);

                if ($item) {
                    $this->assignItem($itemId, $userId, $item['version'], $assignedBy);
                    $results['success'][] = $itemId;
                } else {
                    $results['failed'][] = ['id' => $itemId, 'reason' => 'Not found'];
                }
            } catch (\Exception $e) {
                $results['failed'][] = ['id' => $itemId, 'reason' => $e->getMessage()];
            }
        }

        return $results;
    }
}
