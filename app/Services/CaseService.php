<?php
/**
 * CREODENT Integrated Web Operations System
 * Case Service - Business logic for case operations
 *
 * This service coordinates between repositories and handles:
 * - Case creation/update with audit logging
 * - Evolution data import and normalization
 * - Department-specific JSON storage
 * - Optimistic locking conflicts
 */

declare(strict_types=1);

class CaseService {
    private CaseRepository $caseRepo;
    private CaseItemRepository $itemRepo;
    private DepartmentRepository $deptRepo;
    private AuditLogRepository $auditRepo;

    public function __construct() {
        $this->caseRepo = new CaseRepository();
        $this->itemRepo = new CaseItemRepository();
        $this->deptRepo = new DepartmentRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Create a new case with items and audit log
     *
     * @param array $data
     * @param int|null $userId
     * @return array ['success' => bool, 'case_id' => int, 'error' => string]
     */
    public function createCase(array $data, ?int $userId = null): array {
        try {
            Database::beginTransaction();

            // Create case
            $caseId = $this->caseRepo->create($data);

            // Create audit log
            $this->auditRepo->log([
                'case_id' => $caseId,
                'user_id' => $userId,
                'action' => 'create',
                'description' => 'Case created',
                'after' => $data,
            ]);

            // Create default items if specified
            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $itemData['case_id'] = $caseId;
                    $this->itemRepo->create($itemData);
                }
            }

            Database::commit();

            return [
                'success' => true,
                'case_id' => $caseId,
            ];

        } catch (Exception $e) {
            Database::rollback();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update case with optimistic locking
     *
     * @param int $caseId
     * @param array $data
     * @param int $currentVersion
     * @param int|null $userId
     * @return array ['success' => bool, 'error' => string]
     */
    public function updateCase(int $caseId, array $data, int $currentVersion, ?int $userId = null): array {
        try {
            // Get current state for audit log
            $before = $this->caseRepo->findById($caseId);

            if (!$before) {
                return ['success' => false, 'error' => 'Case not found'];
            }

            // Attempt update with optimistic locking
            $updated = $this->caseRepo->update($caseId, $data, $currentVersion);

            if (!$updated) {
                return [
                    'success' => false,
                    'error' => 'CONFLICT',
                    'message' => 'This case was updated by another user. Please refresh and try again.',
                ];
            }

            // Log the change
            $this->auditRepo->log([
                'case_id' => $caseId,
                'user_id' => $userId,
                'action' => 'update',
                'description' => 'Case updated',
                'before' => $before,
                'after' => $data,
            ]);

            return ['success' => true];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import case from Evolution Web Portal
     *
     * @param array $evolutionData Raw data from Evolution API
     * @return array ['success' => bool, 'case_id' => int, 'error' => string]
     */
    public function importFromEvolution(array $evolutionData): array {
        try {
            Database::beginTransaction();

            // Normalize Evolution data to our schema
            $caseData = $this->normalizeEvolutionData($evolutionData);

            // Upsert case
            $caseId = $this->caseRepo->upsert($caseData);

            // Create/update department-specific items
            $this->createItemsFromEvolutionData($caseId, $evolutionData);

            // Log import
            $this->auditRepo->log([
                'case_id' => $caseId,
                'user_id' => null,
                'action' => 'import_evo',
                'description' => 'Imported from Evolution Web Portal',
                'after' => $evolutionData,
            ]);

            Database::commit();

            return [
                'success' => true,
                'case_id' => $caseId,
            ];

        } catch (Exception $e) {
            Database::rollback();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize Evolution data to our database schema
     *
     * @param array $data
     * @return array
     */
    private function normalizeEvolutionData(array $data): array {
        return [
            'external_case_no' => $data['casenumber'] ?? $data['case_number'] ?? 'EVO-' . uniqid(),
            'source' => 'evo',
            'patient_name' => $data['patient_name'] ?? $data['patientname'] ?? null,
            'lab_name' => $data['lab_name'] ?? $data['labname'] ?? null,
            'location' => $data['location'] ?? null,
            'due_date' => $data['due_date'] ?? $data['duedate'] ?? null,
            'status' => 'new',
            'raw_payload' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Create case items based on Evolution data
     *
     * @param int $caseId
     * @param array $evolutionData
     */
    private function createItemsFromEvolutionData(int $caseId, array $evolutionData): void {
        // Determine department based on case type/instructions
        // This is a simplified version - customize based on your Evolution data structure

        $instructions = $evolutionData['instructions'] ?? '';
        $caseType = $evolutionData['type'] ?? '';

        // Map to departments (customize based on your business logic)
        $departmentMapping = config('department_mapping');

        if (stripos($instructions, 'solidex') !== false || stripos($caseType, 'solidex') !== false) {
            $dept = $this->deptRepo->findByCode('SOLIDEX');
            if ($dept) {
                $this->itemRepo->create([
                    'case_id' => $caseId,
                    'department_id' => $dept['id'],
                    'work_type' => 'SOLIDEX',
                    'tooth_no' => $evolutionData['tooth_no'] ?? null,
                    'count' => $evolutionData['count'] ?? 1,
                    'instruction' => $instructions,
                    'status' => 'pending',
                ]);
            }
        }

        if (stripos($instructions, '3d') !== false || stripos($caseType, 'print') !== false) {
            $dept = $this->deptRepo->findByCode('PRINT3D');
            if ($dept) {
                $this->itemRepo->create([
                    'case_id' => $caseId,
                    'department_id' => $dept['id'],
                    'work_type' => 'PRINT3D',
                    'tooth_no' => $evolutionData['tooth_no'] ?? null,
                    'count' => $evolutionData['count'] ?? 1,
                    'instruction' => $instructions,
                    'status' => 'pending',
                ]);
            }
        }

        if (stripos($instructions, 'cocr') !== false || stripos($instructions, 'zest') !== false) {
            $dept = $this->deptRepo->findByCode('COCR');
            if ($dept) {
                $this->itemRepo->create([
                    'case_id' => $caseId,
                    'department_id' => $dept['id'],
                    'work_type' => 'COCR',
                    'tooth_no' => $evolutionData['tooth_no'] ?? null,
                    'count' => $evolutionData['count'] ?? 1,
                    'instruction' => $instructions,
                    'status' => 'pending',
                ]);
            }
        }
    }

    /**
     * Get case with all related data
     *
     * @param int $caseId
     * @return array|null
     */
    public function getCaseDetails(int $caseId): ?array {
        $case = $this->caseRepo->findWithItems($caseId);

        if (!$case) {
            return null;
        }

        // Get audit logs
        $case['audit_logs'] = $this->auditRepo->getByCaseId($caseId, 20);

        // Get department-specific JSON data if exists
        $case['solidex_data'] = Database::fetchOne(
            "SELECT payload_json FROM solidex_orders WHERE case_id = :case_id ORDER BY id DESC LIMIT 1",
            ['case_id' => $caseId]
        );

        $case['print3d_data'] = Database::fetchOne(
            "SELECT payload_json FROM print3d_orders WHERE case_id = :case_id ORDER BY id DESC LIMIT 1",
            ['case_id' => $caseId]
        );

        $case['cocr_data'] = Database::fetchOne(
            "SELECT payload_json FROM cocr_orders WHERE case_id = :case_id ORDER BY id DESC LIMIT 1",
            ['case_id' => $caseId]
        );

        return $case;
    }

    /**
     * Update case item with optimistic locking
     *
     * @param int $itemId
     * @param array $data
     * @param int $currentVersion
     * @param int|null $userId
     * @return array
     */
    public function updateCaseItem(int $itemId, array $data, int $currentVersion, ?int $userId = null): array {
        try {
            $before = $this->itemRepo->findById($itemId);

            if (!$before) {
                return ['success' => false, 'error' => 'Case item not found'];
            }

            $updated = $this->itemRepo->update($itemId, $data, $currentVersion);

            if (!$updated) {
                return [
                    'success' => false,
                    'error' => 'CONFLICT',
                    'message' => 'This item was updated by another user. Please refresh and try again.',
                ];
            }

            $this->auditRepo->log([
                'case_id' => $before['case_id'],
                'case_item_id' => $itemId,
                'user_id' => $userId,
                'action' => 'update',
                'description' => 'Case item updated',
                'before' => $before,
                'after' => $data,
            ]);

            return ['success' => true];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Assign case item to worker
     *
     * @param int $itemId
     * @param int $userId
     * @param int $currentVersion
     * @param int|null $assignedBy
     * @return array
     */
    public function assignItem(int $itemId, int $userId, int $currentVersion, ?int $assignedBy = null): array {
        try {
            $before = $this->itemRepo->findById($itemId);

            if (!$before) {
                return ['success' => false, 'error' => 'Case item not found'];
            }

            $updated = $this->itemRepo->assign($itemId, $userId, $currentVersion);

            if (!$updated) {
                return [
                    'success' => false,
                    'error' => 'CONFLICT',
                    'message' => 'This item was updated by another user. Please refresh and try again.',
                ];
            }

            $this->auditRepo->log([
                'case_id' => $before['case_id'],
                'case_item_id' => $itemId,
                'user_id' => $assignedBy,
                'action' => 'assign',
                'description' => "Case item assigned to user ID $userId",
            ]);

            return ['success' => true];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
