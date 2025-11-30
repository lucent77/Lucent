<?php
/**
 * Prescription Service
 * Handles prescription creation, validation, and management
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/config/database.php';

class PrescriptionService
{
    /**
     * Create a new prescription from case data
     *
     * @param int $caseId Case ID
     * @param array $prescriptionData Prescription data
     * @return array Result
     */
    public function createPrescription(int $caseId, array $prescriptionData): array
    {
        try {
            Database::beginTransaction();

            // Generate prescription number
            $prescriptionNumber = $this->generatePrescriptionNumber();

            // Prepare medications JSON
            $medications = json_encode(
                $prescriptionData['medications'] ?? [],
                JSON_UNESCAPED_UNICODE
            );

            // Insert prescription record
            $sql = "INSERT INTO prescriptions
                    (case_id, prescription_number, doctor_id, patient_name, patient_dob,
                     patient_gender, diagnosis, medications, instructions, warnings, follow_up_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            Database::execute($sql, [
                $caseId,
                $prescriptionNumber,
                $prescriptionData['doctor_id'] ?? null,
                $prescriptionData['patient_name'] ?? '',
                $prescriptionData['patient_dob'] ?? null,
                $prescriptionData['patient_gender'] ?? null,
                $prescriptionData['diagnosis'] ?? '',
                $medications,
                $prescriptionData['instructions'] ?? null,
                $prescriptionData['warnings'] ?? null,
                $prescriptionData['follow_up_date'] ?? null
            ]);

            $prescriptionId = (int)Database::lastInsertId();

            // Update case status to confirmed
            $this->updateCaseStatus($caseId, 'confirmed');

            // Add to front desk queue
            $this->addToFrontDeskQueue($caseId, $prescriptionData);

            Database::commit();

            return [
                'success' => true,
                'prescription_id' => $prescriptionId,
                'prescription_number' => $prescriptionNumber,
                'message' => 'Prescription created successfully'
            ];
        } catch (Exception $e) {
            Database::rollback();

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing prescription
     *
     * @param int $prescriptionId Prescription ID
     * @param array $updateData Data to update
     * @return array Result
     */
    public function updatePrescription(int $prescriptionId, array $updateData): array
    {
        try {
            $updates = [];
            $params = [];

            $allowedFields = [
                'patient_name', 'patient_dob', 'patient_gender',
                'diagnosis', 'instructions', 'warnings', 'follow_up_date'
            ];

            foreach ($allowedFields as $field) {
                if (isset($updateData[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $updateData[$field];
                }
            }

            // Handle medications separately (JSON field)
            if (isset($updateData['medications'])) {
                $updates[] = "medications = ?";
                $params[] = json_encode($updateData['medications'], JSON_UNESCAPED_UNICODE);
            }

            if (empty($updates)) {
                return [
                    'success' => false,
                    'error' => 'No fields to update'
                ];
            }

            $params[] = $prescriptionId;

            $sql = "UPDATE prescriptions SET " . implode(', ', $updates) . " WHERE id = ?";
            Database::execute($sql, $params);

            return [
                'success' => true,
                'message' => 'Prescription updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sign/confirm a prescription
     *
     * @param int $prescriptionId Prescription ID
     * @param int|null $doctorId Doctor ID who signed
     * @return array Result
     */
    public function signPrescription(int $prescriptionId, ?int $doctorId = null): array
    {
        try {
            $sql = "UPDATE prescriptions
                    SET is_signed = 1,
                        signed_at = NOW(),
                        doctor_id = COALESCE(?, doctor_id)
                    WHERE id = ?";

            Database::execute($sql, [$doctorId, $prescriptionId]);

            // Update related case
            $prescription = $this->getPrescriptionById($prescriptionId);
            if ($prescription) {
                $this->updateCaseStatus($prescription['case_id'], 'completed');
            }

            return [
                'success' => true,
                'message' => 'Prescription signed successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get prescription by ID
     *
     * @param int $prescriptionId Prescription ID
     * @return array|null Prescription data
     */
    public function getPrescriptionById(int $prescriptionId): ?array
    {
        $sql = "SELECT p.*, d.name as doctor_name, d.specialty as doctor_specialty
                FROM prescriptions p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                WHERE p.id = ?";

        $stmt = Database::execute($sql, [$prescriptionId]);
        $prescription = $stmt->fetch();

        if ($prescription) {
            $prescription['medications'] = json_decode($prescription['medications'], true) ?? [];
        }

        return $prescription ?: null;
    }

    /**
     * Get prescription by case ID
     *
     * @param int $caseId Case ID
     * @return array|null Prescription data
     */
    public function getPrescriptionByCaseId(int $caseId): ?array
    {
        $sql = "SELECT p.*, d.name as doctor_name, d.specialty as doctor_specialty
                FROM prescriptions p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                WHERE p.case_id = ?
                ORDER BY p.created_at DESC
                LIMIT 1";

        $stmt = Database::execute($sql, [$caseId]);
        $prescription = $stmt->fetch();

        if ($prescription) {
            $prescription['medications'] = json_decode($prescription['medications'], true) ?? [];
        }

        return $prescription ?: null;
    }

    /**
     * Get all prescriptions with filters
     *
     * @param array $filters Filter criteria
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Prescriptions
     */
    public function getPrescriptions(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['doctor_id'])) {
            $conditions[] = "p.doctor_id = ?";
            $params[] = $filters['doctor_id'];
        }

        if (!empty($filters['is_signed'])) {
            $conditions[] = "p.is_signed = ?";
            $params[] = $filters['is_signed'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(p.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(p.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(p.patient_name LIKE ? OR p.prescription_number LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT p.*, d.name as doctor_name
                FROM prescriptions p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                $whereClause
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = Database::execute($sql, $params);
        $prescriptions = $stmt->fetchAll();

        foreach ($prescriptions as &$prescription) {
            $prescription['medications'] = json_decode($prescription['medications'], true) ?? [];
        }

        return $prescriptions;
    }

    /**
     * Save prescription fields from structured data
     *
     * @param int $caseId Case ID
     * @param array $structuredData Structured case data
     * @return bool Success
     */
    public function savePrescriptionFields(int $caseId, array $structuredData): bool
    {
        try {
            // Clear existing fields for this case
            Database::execute("DELETE FROM prescription_fields WHERE case_id = ?", [$caseId]);

            // Define required fields
            $requiredFields = [
                'patient_name' => 'patient_info.name',
                'chief_complaint' => 'chief_complaint',
                'diagnosis' => 'diagnosis.primary',
                'medication_name' => 'treatment_plan.medications.0.name',
                'medication_dosage' => 'treatment_plan.medications.0.dosage'
            ];

            // Extract and save fields
            foreach ($this->flattenArray($structuredData) as $fieldName => $fieldValue) {
                $isRequired = in_array($fieldName, array_keys($requiredFields));
                $isFilled = !empty($fieldValue) && $fieldValue !== 'null';
                $fieldType = $this->detectFieldType($fieldValue);

                $sql = "INSERT INTO prescription_fields
                        (case_id, field_name, field_value, field_type, is_required, is_filled, source)
                        VALUES (?, ?, ?, ?, ?, ?, 'ai_extracted')";

                Database::execute($sql, [
                    $caseId,
                    $fieldName,
                    is_array($fieldValue) ? json_encode($fieldValue) : (string)$fieldValue,
                    $fieldType,
                    $isRequired ? 1 : 0,
                    $isFilled ? 1 : 0
                ]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error saving prescription fields: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a prescription field
     *
     * @param int $caseId Case ID
     * @param string $fieldName Field name
     * @param mixed $fieldValue Field value
     * @param string $source Source of update
     * @return bool Success
     */
    public function updatePrescriptionField(
        int $caseId,
        string $fieldName,
        $fieldValue,
        string $source = 'manual'
    ): bool {
        try {
            $isFilled = !empty($fieldValue);

            $sql = "INSERT INTO prescription_fields
                    (case_id, field_name, field_value, is_filled, source)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    field_value = VALUES(field_value),
                    is_filled = VALUES(is_filled),
                    source = VALUES(source),
                    updated_at = NOW()";

            Database::execute($sql, [
                $caseId,
                $fieldName,
                is_array($fieldValue) ? json_encode($fieldValue) : (string)$fieldValue,
                $isFilled ? 1 : 0,
                $source
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Error updating prescription field: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get prescription fields for a case
     *
     * @param int $caseId Case ID
     * @return array Fields
     */
    public function getPrescriptionFields(int $caseId): array
    {
        $sql = "SELECT * FROM prescription_fields WHERE case_id = ? ORDER BY field_name";
        $stmt = Database::execute($sql, [$caseId]);

        return $stmt->fetchAll();
    }

    /**
     * Get unfilled required fields for a case
     *
     * @param int $caseId Case ID
     * @return array Unfilled fields
     */
    public function getUnfilledRequiredFields(int $caseId): array
    {
        $sql = "SELECT * FROM prescription_fields
                WHERE case_id = ? AND is_required = 1 AND is_filled = 0";
        $stmt = Database::execute($sql, [$caseId]);

        return $stmt->fetchAll();
    }

    /**
     * Add case to front desk queue
     *
     * @param int $caseId Case ID
     * @param array $prescriptionData Prescription data
     * @return int Queue ID
     */
    public function addToFrontDeskQueue(int $caseId, array $prescriptionData): int
    {
        // Count medications
        $medicationsCount = count($prescriptionData['medications'] ?? []);

        // Generate summary
        $summary = sprintf(
            "환자: %s | 진단: %s | 처방 약품: %d개",
            $prescriptionData['patient_name'] ?? 'Unknown',
            $prescriptionData['diagnosis'] ?? 'N/A',
            $medicationsCount
        );

        // Determine priority based on keywords
        $priority = 'normal';
        $urgentKeywords = ['urgent', 'emergency', '급성', '응급', '긴급'];
        $diagnosis = strtolower($prescriptionData['diagnosis'] ?? '');

        foreach ($urgentKeywords as $keyword) {
            if (strpos($diagnosis, $keyword) !== false) {
                $priority = 'urgent';
                break;
            }
        }

        $sql = "INSERT INTO frontdesk_queue
                (case_id, priority, prescription_summary, medications_count, estimated_wait_minutes)
                VALUES (?, ?, ?, ?, ?)";

        // Estimate wait time based on queue length
        $waitTime = $this->estimateWaitTime();

        Database::execute($sql, [
            $caseId,
            $priority,
            $summary,
            $medicationsCount,
            $waitTime
        ]);

        return (int)Database::lastInsertId();
    }

    /**
     * Get front desk queue
     *
     * @param string|null $status Filter by status
     * @return array Queue items
     */
    public function getFrontDeskQueue(?string $status = null): array
    {
        $sql = "SELECT fq.*, c.case_number, c.patient_name, c.diagnosis,
                       d.name as doctor_name, c.medications
                FROM frontdesk_queue fq
                JOIN cases c ON fq.case_id = c.id
                LEFT JOIN doctors d ON c.doctor_id = d.id";

        $params = [];

        if ($status) {
            $sql .= " WHERE fq.status = ?";
            $params[] = $status;
        } else {
            $sql .= " WHERE fq.status IN ('waiting', 'processing', 'ready')";
        }

        $sql .= " ORDER BY
                    FIELD(fq.priority, 'urgent', 'high', 'normal', 'low'),
                    fq.created_at ASC";

        $stmt = Database::execute($sql, $params);
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $item['medications'] = json_decode($item['medications'], true) ?? [];
            $item['wait_minutes'] = $this->calculateWaitMinutes($item['created_at']);
        }

        return $items;
    }

    /**
     * Update front desk queue item status
     *
     * @param int $queueId Queue ID
     * @param string $status New status
     * @param string|null $assignedTo Assigned staff
     * @return bool Success
     */
    public function updateQueueStatus(int $queueId, string $status, ?string $assignedTo = null): bool
    {
        $sql = "UPDATE frontdesk_queue
                SET status = ?,
                    assigned_to = COALESCE(?, assigned_to),
                    processed_at = CASE WHEN ? = 'processing' THEN NOW() ELSE processed_at END,
                    dispensed_at = CASE WHEN ? = 'dispensed' THEN NOW() ELSE dispensed_at END
                WHERE id = ?";

        Database::execute($sql, [$status, $assignedTo, $status, $status, $queueId]);

        return true;
    }

    /**
     * Get queue statistics
     *
     * @return array Statistics
     */
    public function getQueueStatistics(): array
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
                    SUM(CASE WHEN status = 'dispensed' THEN 1 ELSE 0 END) as dispensed,
                    AVG(TIMESTAMPDIFF(MINUTE, created_at, COALESCE(dispensed_at, NOW()))) as avg_wait_time
                FROM frontdesk_queue
                WHERE DATE(created_at) = CURDATE()";

        $stmt = Database::execute($sql);
        return $stmt->fetch();
    }

    /**
     * Generate unique prescription number
     *
     * @return string Prescription number
     */
    private function generatePrescriptionNumber(): string
    {
        $datePrefix = date('Ymd');

        $sql = "SELECT COUNT(*) + 1 as next_num
                FROM prescriptions
                WHERE DATE(created_at) = CURDATE()";

        $stmt = Database::execute($sql);
        $result = $stmt->fetch();

        return sprintf('RX-%s-%04d', $datePrefix, $result['next_num']);
    }

    /**
     * Update case status
     *
     * @param int $caseId Case ID
     * @param string $status New status
     */
    private function updateCaseStatus(int $caseId, string $status): void
    {
        $sql = "UPDATE cases
                SET status = ?,
                    confirmed_at = CASE WHEN ? = 'confirmed' THEN NOW() ELSE confirmed_at END,
                    completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END
                WHERE id = ?";

        Database::execute($sql, [$status, $status, $status, $caseId]);
    }

    /**
     * Estimate wait time based on current queue
     *
     * @return int Estimated minutes
     */
    private function estimateWaitTime(): int
    {
        $sql = "SELECT COUNT(*) as pending
                FROM frontdesk_queue
                WHERE status IN ('waiting', 'processing')
                AND DATE(created_at) = CURDATE()";

        $stmt = Database::execute($sql);
        $result = $stmt->fetch();

        // Estimate 5 minutes per pending item
        return ($result['pending'] ?? 0) * 5;
    }

    /**
     * Calculate actual wait minutes
     *
     * @param string $createdAt Creation timestamp
     * @return int Minutes waited
     */
    private function calculateWaitMinutes(string $createdAt): int
    {
        $created = new DateTime($createdAt);
        $now = new DateTime();
        $diff = $now->diff($created);

        return ($diff->h * 60) + $diff->i;
    }

    /**
     * Flatten nested array to dot notation keys
     *
     * @param array $array Array to flatten
     * @param string $prefix Key prefix
     * @return array Flattened array
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value) && !$this->isNumericArray($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if array is numeric (list)
     *
     * @param array $array Array to check
     * @return bool Is numeric
     */
    private function isNumericArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Detect field type from value
     *
     * @param mixed $value Value to check
     * @return string Field type
     */
    private function detectFieldType($value): string
    {
        if (is_array($value)) {
            return $this->isNumericArray($value) ? 'array' : 'object';
        }
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_numeric($value)) {
            return 'number';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return 'date';
        }

        return 'text';
    }
}
