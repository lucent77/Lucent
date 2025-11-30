<?php
/**
 * Cases API
 * GET/POST/PUT /api/cases.php
 *
 * Manages case CRUD operations
 */

require_once __DIR__ . '/bootstrap.php';

// Allow multiple methods
requireMethod(['GET', 'POST', 'PUT', 'DELETE']);

try {
    $caseModel = new CaseModel();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get case(s)
            $caseId = getParam('id');
            $caseNumber = getParam('case_number');

            if ($caseId) {
                // Get single case by ID
                $case = $caseModel->getById((int)$caseId);
                if (!$case) {
                    errorResponse('Case not found', 404);
                }

                // Get related audio files
                $audioService = new AudioService();
                $audioFiles = $audioService->getAudioByCaseId((int)$caseId);

                // Get follow-up questions
                $followupModel = new FollowupQuestionModel();
                $followupQuestions = $followupModel->getByCaseId((int)$caseId);

                // Get prescription if exists
                $prescriptionModel = new PrescriptionModel();
                $prescription = $prescriptionModel->getByCaseId((int)$caseId);

                $case['audio_files'] = $audioFiles;
                $case['followup_questions'] = $followupQuestions;
                $case['prescription'] = $prescription;

                successResponse(['case' => $case]);
            } elseif ($caseNumber) {
                // Get single case by case number
                $case = $caseModel->getByCaseNumber($caseNumber);
                if (!$case) {
                    errorResponse('Case not found', 404);
                }
                successResponse(['case' => $case]);
            } else {
                // Get all cases with filters
                $filters = [
                    'status' => getParam('status'),
                    'doctor_id' => getParam('doctor_id'),
                    'date_from' => getParam('date_from'),
                    'date_to' => getParam('date_to'),
                    'search' => getParam('search')
                ];

                // Remove null filters
                $filters = array_filter($filters, fn($v) => $v !== null);

                $limit = (int)(getParam('limit') ?? 50);
                $offset = (int)(getParam('offset') ?? 0);

                $cases = $caseModel->getAll($filters, $limit, $offset);
                $statistics = $caseModel->getStatistics(
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null
                );

                successResponse([
                    'cases' => $cases,
                    'total' => count($cases),
                    'statistics' => $statistics,
                    'filters' => $filters
                ]);
            }
            break;

        case 'POST':
            // Create new case
            $data = array_merge($_POST, getJsonBody());

            $result = $caseModel->create([
                'doctor_id' => $data['doctor_id'] ?? null,
                'patient_name' => $data['patient_name'] ?? null,
                'patient_dob' => $data['patient_dob'] ?? null,
                'patient_gender' => $data['patient_gender'] ?? null,
                'patient_phone' => $data['patient_phone'] ?? null,
                'chief_complaint' => $data['chief_complaint'] ?? null
            ]);

            if (!$result['success']) {
                errorResponse('Failed to create case: ' . ($result['error'] ?? 'Unknown error'));
            }

            successResponse([
                'case_id' => $result['case_id'],
                'case_number' => $result['case_number']
            ], 'Case created successfully');
            break;

        case 'PUT':
            // Update existing case
            $data = array_merge($_POST, getJsonBody());

            if (empty($data['id'])) {
                errorResponse('Case ID is required');
            }

            $caseId = (int)$data['id'];
            unset($data['id']);

            // Verify case exists
            $case = $caseModel->getById($caseId);
            if (!$case) {
                errorResponse('Case not found', 404);
            }

            $success = $caseModel->update($caseId, $data);

            if (!$success) {
                errorResponse('Failed to update case');
            }

            successResponse([
                'case_id' => $caseId,
                'updated' => true
            ], 'Case updated successfully');
            break;

        case 'DELETE':
            // Delete case
            $caseId = getParam('id');

            if (!$caseId) {
                errorResponse('Case ID is required');
            }

            $case = $caseModel->getById((int)$caseId);
            if (!$case) {
                errorResponse('Case not found', 404);
            }

            $success = $caseModel->delete((int)$caseId);

            if (!$success) {
                errorResponse('Failed to delete case');
            }

            successResponse([
                'case_id' => (int)$caseId,
                'deleted' => true
            ], 'Case deleted successfully');
            break;
    }

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
