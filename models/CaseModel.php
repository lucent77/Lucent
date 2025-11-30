<?php
/**
 * Case Model
 * Handles all case/consultation data operations
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/config/database.php';

class CaseModel
{
    /**
     * Create a new case
     *
     * @param array $data Initial case data
     * @return array Result with case_id and case_number
     */
    public function create(array $data = []): array
    {
        try {
            // Generate case number
            $caseNumber = $this->generateCaseNumber();

            $sql = "INSERT INTO cases
                    (case_number, doctor_id, patient_name, patient_dob, patient_gender,
                     patient_phone, chief_complaint, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'recording')";

            Database::execute($sql, [
                $caseNumber,
                $data['doctor_id'] ?? null,
                $data['patient_name'] ?? null,
                $data['patient_dob'] ?? null,
                $data['patient_gender'] ?? null,
                $data['patient_phone'] ?? null,
                $data['chief_complaint'] ?? null
            ]);

            $caseId = (int)Database::lastInsertId();

            return [
                'success' => true,
                'case_id' => $caseId,
                'case_number' => $caseNumber
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get case by ID
     *
     * @param int $caseId Case ID
     * @return array|null Case data
     */
    public function getById(int $caseId): ?array
    {
        $sql = "SELECT c.*, d.name as doctor_name, d.specialty as doctor_specialty
                FROM cases c
                LEFT JOIN doctors d ON c.doctor_id = d.id
                WHERE c.id = ?";

        $stmt = Database::execute($sql, [$caseId]);
        $case = $stmt->fetch();

        if ($case) {
            $case = $this->parseJsonFields($case);
        }

        return $case ?: null;
    }

    /**
     * Get case by case number
     *
     * @param string $caseNumber Case number
     * @return array|null Case data
     */
    public function getByCaseNumber(string $caseNumber): ?array
    {
        $sql = "SELECT c.*, d.name as doctor_name, d.specialty as doctor_specialty
                FROM cases c
                LEFT JOIN doctors d ON c.doctor_id = d.id
                WHERE c.case_number = ?";

        $stmt = Database::execute($sql, [$caseNumber]);
        $case = $stmt->fetch();

        if ($case) {
            $case = $this->parseJsonFields($case);
        }

        return $case ?: null;
    }

    /**
     * Update case data
     *
     * @param int $caseId Case ID
     * @param array $data Data to update
     * @return bool Success
     */
    public function update(int $caseId, array $data): bool
    {
        try {
            $updates = [];
            $params = [];

            // Standard text/date fields
            $allowedFields = [
                'doctor_id', 'patient_name', 'patient_dob', 'patient_gender',
                'patient_phone', 'patient_id_number', 'chief_complaint', 'symptoms',
                'diagnosis', 'treatment_plan', 'allergies', 'notes', 'status'
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            // JSON fields
            $jsonFields = ['medications', 'vital_signs', 'ai_structured_data', 'missing_fields'];
            foreach ($jsonFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[] = "$field = ?";
                    $params[] = is_array($data[$field])
                        ? json_encode($data[$field], JSON_UNESCAPED_UNICODE)
                        : $data[$field];
                }
            }

            if (empty($updates)) {
                return true;
            }

            $params[] = $caseId;

            $sql = "UPDATE cases SET " . implode(', ', $updates) . " WHERE id = ?";
            Database::execute($sql, $params);

            return true;
        } catch (Exception $e) {
            error_log("Error updating case: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update case status
     *
     * @param int $caseId Case ID
     * @param string $status New status
     * @return bool Success
     */
    public function updateStatus(int $caseId, string $status): bool
    {
        $validStatuses = [
            'recording', 'transcribing', 'analyzing', 'followup',
            'pending_confirmation', 'confirmed', 'completed', 'cancelled'
        ];

        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $sql = "UPDATE cases
                SET status = ?,
                    confirmed_at = CASE WHEN ? = 'confirmed' THEN NOW() ELSE confirmed_at END,
                    completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END
                WHERE id = ?";

        Database::execute($sql, [$status, $status, $status, $caseId]);

        return true;
    }

    /**
     * Update structured data from AI analysis
     *
     * @param int $caseId Case ID
     * @param array $structuredData AI-extracted structured data
     * @param array|null $missingFields Missing fields list
     * @return bool Success
     */
    public function updateStructuredData(int $caseId, array $structuredData, ?array $missingFields = null): bool
    {
        try {
            // Extract patient info if available
            $patientInfo = $structuredData['patient_info'] ?? [];
            $diagnosis = $structuredData['diagnosis'] ?? [];
            $treatment = $structuredData['treatment_plan'] ?? [];

            $sql = "UPDATE cases SET
                    ai_structured_data = ?,
                    missing_fields = ?,
                    patient_name = COALESCE(NULLIF(?, ''), patient_name),
                    patient_gender = COALESCE(?, patient_gender),
                    chief_complaint = COALESCE(NULLIF(?, ''), chief_complaint),
                    symptoms = COALESCE(NULLIF(?, ''), symptoms),
                    diagnosis = COALESCE(NULLIF(?, ''), diagnosis),
                    treatment_plan = COALESCE(NULLIF(?, ''), treatment_plan),
                    medications = ?,
                    allergies = COALESCE(NULLIF(?, ''), allergies),
                    vital_signs = ?,
                    status = 'analyzing'
                    WHERE id = ?";

            $symptomsText = is_array($structuredData['symptoms'] ?? null)
                ? implode(', ', $structuredData['symptoms'])
                : ($structuredData['symptoms'] ?? null);

            $allergiesText = is_array($structuredData['allergies'] ?? null)
                ? implode(', ', $structuredData['allergies'])
                : ($structuredData['allergies'] ?? null);

            Database::execute($sql, [
                json_encode($structuredData, JSON_UNESCAPED_UNICODE),
                $missingFields ? json_encode($missingFields, JSON_UNESCAPED_UNICODE) : null,
                $patientInfo['name'] ?? null,
                $patientInfo['gender'] ?? null,
                $structuredData['chief_complaint'] ?? null,
                $symptomsText,
                $diagnosis['primary'] ?? null,
                is_array($treatment) ? json_encode($treatment, JSON_UNESCAPED_UNICODE) : $treatment,
                json_encode($treatment['medications'] ?? [], JSON_UNESCAPED_UNICODE),
                $allergiesText,
                json_encode($structuredData['vital_signs'] ?? [], JSON_UNESCAPED_UNICODE),
                $caseId
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Error updating structured data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Increment follow-up count
     *
     * @param int $caseId Case ID
     * @return int New count
     */
    public function incrementFollowupCount(int $caseId): int
    {
        $sql = "UPDATE cases SET followup_count = followup_count + 1 WHERE id = ?";
        Database::execute($sql, [$caseId]);

        $case = $this->getById($caseId);
        return $case['followup_count'] ?? 0;
    }

    /**
     * Get all cases with filters
     *
     * @param array $filters Filter criteria
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Cases
     */
    public function getAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = implode(',', array_fill(0, count($filters['status']), '?'));
                $conditions[] = "c.status IN ($placeholders)";
                $params = array_merge($params, $filters['status']);
            } else {
                $conditions[] = "c.status = ?";
                $params[] = $filters['status'];
            }
        }

        if (!empty($filters['doctor_id'])) {
            $conditions[] = "c.doctor_id = ?";
            $params[] = $filters['doctor_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(c.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(c.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(c.patient_name LIKE ? OR c.case_number LIKE ? OR c.diagnosis LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT c.*, d.name as doctor_name
                FROM cases c
                LEFT JOIN doctors d ON c.doctor_id = d.id
                $whereClause
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = Database::execute($sql, $params);
        $cases = $stmt->fetchAll();

        foreach ($cases as &$case) {
            $case = $this->parseJsonFields($case);
        }

        return $cases;
    }

    /**
     * Get active cases (not completed or cancelled)
     *
     * @return array Active cases
     */
    public function getActiveCases(): array
    {
        return $this->getAll([
            'status' => ['recording', 'transcribing', 'analyzing', 'followup', 'pending_confirmation', 'confirmed']
        ]);
    }

    /**
     * Get today's cases
     *
     * @return array Today's cases
     */
    public function getTodaysCases(): array
    {
        return $this->getAll([
            'date_from' => date('Y-m-d'),
            'date_to' => date('Y-m-d')
        ]);
    }

    /**
     * Get case statistics
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
                    COUNT(*) as total_cases,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN status IN ('recording', 'transcribing', 'analyzing', 'followup', 'pending_confirmation') THEN 1 ELSE 0 END) as in_progress,
                    AVG(followup_count) as avg_followups,
                    AVG(TIMESTAMPDIFF(MINUTE, created_at, COALESCE(completed_at, NOW()))) as avg_completion_minutes
                FROM cases
                $whereClause";

        $stmt = Database::execute($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Delete case and all related data
     *
     * @param int $caseId Case ID
     * @return bool Success
     */
    public function delete(int $caseId): bool
    {
        try {
            // Delete associated files first
            $sql = "SELECT file_path FROM case_audio WHERE case_id = ?";
            $stmt = Database::execute($sql, [$caseId]);
            $audioFiles = $stmt->fetchAll();

            foreach ($audioFiles as $audio) {
                if (file_exists($audio['file_path'])) {
                    unlink($audio['file_path']);
                }
            }

            // Delete TTS audio files
            $sql = "SELECT tts_audio_path FROM followup_questions WHERE case_id = ? AND tts_audio_path IS NOT NULL";
            $stmt = Database::execute($sql, [$caseId]);
            $ttsFiles = $stmt->fetchAll();

            foreach ($ttsFiles as $tts) {
                if (file_exists($tts['tts_audio_path'])) {
                    unlink($tts['tts_audio_path']);
                }
            }

            // Delete case (cascades to related tables due to foreign keys)
            $sql = "DELETE FROM cases WHERE id = ?";
            Database::execute($sql, [$caseId]);

            return true;
        } catch (Exception $e) {
            error_log("Error deleting case: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate unique case number
     *
     * @return string Case number
     */
    private function generateCaseNumber(): string
    {
        $datePrefix = date('Ymd');

        $sql = "SELECT COUNT(*) + 1 as next_num
                FROM cases
                WHERE DATE(created_at) = CURDATE()";

        $stmt = Database::execute($sql);
        $result = $stmt->fetch();

        return sprintf('CASE-%s-%04d', $datePrefix, $result['next_num']);
    }

    /**
     * Parse JSON fields in case data
     *
     * @param array $case Case data
     * @return array Parsed case data
     */
    private function parseJsonFields(array $case): array
    {
        $jsonFields = ['medications', 'vital_signs', 'ai_structured_data', 'missing_fields'];

        foreach ($jsonFields as $field) {
            if (isset($case[$field]) && is_string($case[$field])) {
                $case[$field] = json_decode($case[$field], true) ?? [];
            }
        }

        return $case;
    }
}

/**
 * Follow-up Question Model Helper
 * Handles follow-up question operations
 */
class FollowupQuestionModel
{
    /**
     * Create a new follow-up question
     *
     * @param int $caseId Case ID
     * @param string $questionText Question text
     * @param string|null $targetField Target field for the question
     * @return int Question ID
     */
    public function create(int $caseId, string $questionText, ?string $targetField = null): int
    {
        // Get next question number for this case
        $sql = "SELECT COALESCE(MAX(question_number), 0) + 1 as next_num
                FROM followup_questions WHERE case_id = ?";
        $stmt = Database::execute($sql, [$caseId]);
        $result = $stmt->fetch();
        $questionNumber = $result['next_num'];

        $sql = "INSERT INTO followup_questions
                (case_id, question_number, question_text, target_field)
                VALUES (?, ?, ?, ?)";

        Database::execute($sql, [$caseId, $questionNumber, $questionText, $targetField]);

        return (int)Database::lastInsertId();
    }

    /**
     * Get follow-up questions for a case
     *
     * @param int $caseId Case ID
     * @param bool $unansweredOnly Get only unanswered questions
     * @return array Questions
     */
    public function getByCaseId(int $caseId, bool $unansweredOnly = false): array
    {
        $sql = "SELECT * FROM followup_questions WHERE case_id = ?";
        $params = [$caseId];

        if ($unansweredOnly) {
            $sql .= " AND is_answered = 0";
        }

        $sql .= " ORDER BY question_number ASC";

        $stmt = Database::execute($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get next unanswered question for a case
     *
     * @param int $caseId Case ID
     * @return array|null Question data
     */
    public function getNextUnanswered(int $caseId): ?array
    {
        $sql = "SELECT * FROM followup_questions
                WHERE case_id = ? AND is_answered = 0
                ORDER BY question_number ASC
                LIMIT 1";

        $stmt = Database::execute($sql, [$caseId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update question with TTS audio path
     *
     * @param int $questionId Question ID
     * @param string $audioPath Audio file path
     * @return bool Success
     */
    public function updateTtsAudio(int $questionId, string $audioPath): bool
    {
        $sql = "UPDATE followup_questions
                SET tts_audio_path = ?, tts_audio_generated = 1
                WHERE id = ?";

        Database::execute($sql, [$audioPath, $questionId]);
        return true;
    }

    /**
     * Answer a follow-up question
     *
     * @param int $questionId Question ID
     * @param string $answerText Answer text
     * @param int|null $answerAudioId Audio ID if answer was voice
     * @return bool Success
     */
    public function answer(int $questionId, string $answerText, ?int $answerAudioId = null): bool
    {
        $sql = "UPDATE followup_questions
                SET answer_text = ?,
                    answer_audio_id = ?,
                    is_answered = 1,
                    answered_at = NOW()
                WHERE id = ?";

        Database::execute($sql, [$answerText, $answerAudioId, $questionId]);
        return true;
    }
}
