<?php
/**
 * Prescription Model
 * Handles prescription data operations
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/config/database.php';

class PrescriptionModel
{
    /**
     * Create a new prescription
     *
     * @param array $data Prescription data
     * @return array Result with prescription_id
     */
    public function create(array $data): array
    {
        try {
            $prescriptionNumber = $this->generatePrescriptionNumber();

            $sql = "INSERT INTO prescriptions
                    (case_id, prescription_number, doctor_id, patient_name, patient_dob,
                     patient_gender, diagnosis, medications, instructions, warnings, follow_up_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $medications = is_array($data['medications'] ?? null)
                ? json_encode($data['medications'], JSON_UNESCAPED_UNICODE)
                : ($data['medications'] ?? '[]');

            Database::execute($sql, [
                $data['case_id'],
                $prescriptionNumber,
                $data['doctor_id'] ?? null,
                $data['patient_name'],
                $data['patient_dob'] ?? null,
                $data['patient_gender'] ?? null,
                $data['diagnosis'] ?? null,
                $medications,
                $data['instructions'] ?? null,
                $data['warnings'] ?? null,
                $data['follow_up_date'] ?? null
            ]);

            return [
                'success' => true,
                'prescription_id' => (int)Database::lastInsertId(),
                'prescription_number' => $prescriptionNumber
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
    public function getById(int $prescriptionId): ?array
    {
        $sql = "SELECT p.*, d.name as doctor_name, d.specialty as doctor_specialty,
                       c.case_number
                FROM prescriptions p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                LEFT JOIN cases c ON p.case_id = c.id
                WHERE p.id = ?";

        $stmt = Database::execute($sql, [$prescriptionId]);
        $prescription = $stmt->fetch();

        if ($prescription) {
            $prescription['medications'] = json_decode($prescription['medications'], true) ?? [];
        }

        return $prescription ?: null;
    }

    /**
     * Get prescription by prescription number
     *
     * @param string $prescriptionNumber Prescription number
     * @return array|null Prescription data
     */
    public function getByNumber(string $prescriptionNumber): ?array
    {
        $sql = "SELECT p.*, d.name as doctor_name, d.specialty as doctor_specialty,
                       c.case_number
                FROM prescriptions p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                LEFT JOIN cases c ON p.case_id = c.id
                WHERE p.prescription_number = ?";

        $stmt = Database::execute($sql, [$prescriptionNumber]);
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
    public function getByCaseId(int $caseId): ?array
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
     * Update prescription
     *
     * @param int $prescriptionId Prescription ID
     * @param array $data Data to update
     * @return bool Success
     */
    public function update(int $prescriptionId, array $data): bool
    {
        try {
            $updates = [];
            $params = [];

            $allowedFields = [
                'doctor_id', 'patient_name', 'patient_dob', 'patient_gender',
                'diagnosis', 'instructions', 'warnings', 'follow_up_date'
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            // Handle medications separately
            if (isset($data['medications'])) {
                $updates[] = "medications = ?";
                $params[] = is_array($data['medications'])
                    ? json_encode($data['medications'], JSON_UNESCAPED_UNICODE)
                    : $data['medications'];
            }

            if (empty($updates)) {
                return true;
            }

            $params[] = $prescriptionId;

            $sql = "UPDATE prescriptions SET " . implode(', ', $updates) . " WHERE id = ?";
            Database::execute($sql, $params);

            return true;
        } catch (Exception $e) {
            error_log("Error updating prescription: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sign a prescription
     *
     * @param int $prescriptionId Prescription ID
     * @param int|null $doctorId Doctor ID
     * @return bool Success
     */
    public function sign(int $prescriptionId, ?int $doctorId = null): bool
    {
        $sql = "UPDATE prescriptions
                SET is_signed = 1,
                    signed_at = NOW(),
                    doctor_id = COALESCE(?, doctor_id)
                WHERE id = ?";

        Database::execute($sql, [$doctorId, $prescriptionId]);
        return true;
    }

    /**
     * Get all prescriptions with filters
     *
     * @param array $filters Filter criteria
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Prescriptions
     */
    public function getAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['doctor_id'])) {
            $conditions[] = "p.doctor_id = ?";
            $params[] = $filters['doctor_id'];
        }

        if (isset($filters['is_signed'])) {
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
            $conditions[] = "(p.patient_name LIKE ? OR p.prescription_number LIKE ? OR p.diagnosis LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT p.*, d.name as doctor_name, c.case_number
                FROM prescriptions p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                LEFT JOIN cases c ON p.case_id = c.id
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
     * Get today's prescriptions
     *
     * @return array Prescriptions
     */
    public function getTodays(): array
    {
        return $this->getAll([
            'date_from' => date('Y-m-d'),
            'date_to' => date('Y-m-d')
        ]);
    }

    /**
     * Get unsigned prescriptions
     *
     * @return array Unsigned prescriptions
     */
    public function getUnsigned(): array
    {
        return $this->getAll(['is_signed' => 0]);
    }

    /**
     * Get prescription statistics
     *
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @return array Statistics
     */
    public function getStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $conditions = [];
        $params = [];

        if ($dateFrom) {
            $conditions[] = "DATE(created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $conditions[] = "DATE(created_at) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT
                    COUNT(*) as total_prescriptions,
                    SUM(CASE WHEN is_signed = 1 THEN 1 ELSE 0 END) as signed,
                    SUM(CASE WHEN is_signed = 0 THEN 1 ELSE 0 END) as unsigned,
                    COUNT(DISTINCT doctor_id) as doctors_count,
                    COUNT(DISTINCT patient_name) as patients_count
                FROM prescriptions
                $whereClause";

        $stmt = Database::execute($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Delete prescription
     *
     * @param int $prescriptionId Prescription ID
     * @return bool Success
     */
    public function delete(int $prescriptionId): bool
    {
        try {
            // Delete PDF if exists
            $prescription = $this->getById($prescriptionId);
            if ($prescription && !empty($prescription['pdf_path']) && file_exists($prescription['pdf_path'])) {
                unlink($prescription['pdf_path']);
            }

            $sql = "DELETE FROM prescriptions WHERE id = ?";
            Database::execute($sql, [$prescriptionId]);

            return true;
        } catch (Exception $e) {
            error_log("Error deleting prescription: " . $e->getMessage());
            return false;
        }
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
     * Add medication to prescription
     *
     * @param int $prescriptionId Prescription ID
     * @param array $medication Medication data
     * @return bool Success
     */
    public function addMedication(int $prescriptionId, array $medication): bool
    {
        try {
            $prescription = $this->getById($prescriptionId);
            if (!$prescription) {
                return false;
            }

            $medications = $prescription['medications'];
            $medications[] = $medication;

            return $this->update($prescriptionId, ['medications' => $medications]);
        } catch (Exception $e) {
            error_log("Error adding medication: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove medication from prescription
     *
     * @param int $prescriptionId Prescription ID
     * @param int $medicationIndex Index of medication to remove
     * @return bool Success
     */
    public function removeMedication(int $prescriptionId, int $medicationIndex): bool
    {
        try {
            $prescription = $this->getById($prescriptionId);
            if (!$prescription) {
                return false;
            }

            $medications = $prescription['medications'];

            if (!isset($medications[$medicationIndex])) {
                return false;
            }

            array_splice($medications, $medicationIndex, 1);

            return $this->update($prescriptionId, ['medications' => $medications]);
        } catch (Exception $e) {
            error_log("Error removing medication: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update medication in prescription
     *
     * @param int $prescriptionId Prescription ID
     * @param int $medicationIndex Index of medication to update
     * @param array $medicationData New medication data
     * @return bool Success
     */
    public function updateMedication(int $prescriptionId, int $medicationIndex, array $medicationData): bool
    {
        try {
            $prescription = $this->getById($prescriptionId);
            if (!$prescription) {
                return false;
            }

            $medications = $prescription['medications'];

            if (!isset($medications[$medicationIndex])) {
                return false;
            }

            $medications[$medicationIndex] = array_merge($medications[$medicationIndex], $medicationData);

            return $this->update($prescriptionId, ['medications' => $medications]);
        } catch (Exception $e) {
            error_log("Error updating medication: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Doctor Model Helper
 * Handles doctor data operations
 */
class DoctorModel
{
    /**
     * Get doctor by ID
     *
     * @param int $doctorId Doctor ID
     * @return array|null Doctor data
     */
    public function getById(int $doctorId): ?array
    {
        $sql = "SELECT * FROM doctors WHERE id = ?";
        $stmt = Database::execute($sql, [$doctorId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get doctor by code
     *
     * @param string $doctorCode Doctor code
     * @return array|null Doctor data
     */
    public function getByCode(string $doctorCode): ?array
    {
        $sql = "SELECT * FROM doctors WHERE doctor_code = ?";
        $stmt = Database::execute($sql, [$doctorCode]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all active doctors
     *
     * @return array Doctors
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM doctors WHERE is_active = 1 ORDER BY name";
        $stmt = Database::execute($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get doctors by department
     *
     * @param string $department Department name
     * @return array Doctors
     */
    public function getByDepartment(string $department): array
    {
        $sql = "SELECT * FROM doctors WHERE department = ? AND is_active = 1 ORDER BY name";
        $stmt = Database::execute($sql, [$department]);
        return $stmt->fetchAll();
    }

    /**
     * Create a new doctor
     *
     * @param array $data Doctor data
     * @return int Doctor ID
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO doctors
                (doctor_code, name, specialty, email, phone, license_number, department)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        Database::execute($sql, [
            $data['doctor_code'],
            $data['name'],
            $data['specialty'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['license_number'] ?? null,
            $data['department'] ?? null
        ]);

        return (int)Database::lastInsertId();
    }

    /**
     * Update doctor
     *
     * @param int $doctorId Doctor ID
     * @param array $data Data to update
     * @return bool Success
     */
    public function update(int $doctorId, array $data): bool
    {
        $updates = [];
        $params = [];

        $allowedFields = ['name', 'specialty', 'email', 'phone', 'license_number', 'department', 'is_active'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return true;
        }

        $params[] = $doctorId;

        $sql = "UPDATE doctors SET " . implode(', ', $updates) . " WHERE id = ?";
        Database::execute($sql, $params);

        return true;
    }
}
