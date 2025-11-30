<?php
/**
 * Save Prescription API
 * POST /api/save_prescription.php
 *
 * Saves final structured prescription data to MySQL
 * Adds to front desk queue
 * Returns prescription details
 */

require_once __DIR__ . '/bootstrap.php';

// Require POST method
requireMethod('POST');

try {
    // Get request data
    $data = array_merge($_POST, getJsonBody());

    // Validate required fields
    if (empty($data['case_id'])) {
        errorResponse('case_id is required');
    }

    $caseId = (int)$data['case_id'];

    // Initialize services and models
    $prescriptionService = new PrescriptionService();
    $caseModel = new CaseModel();
    $prescriptionModel = new PrescriptionModel();

    // Get case data
    $case = $caseModel->getById($caseId);
    if (!$case) {
        errorResponse('Case not found', 404);
    }

    // Check if prescription already exists
    $existingPrescription = $prescriptionModel->getByCaseId($caseId);

    // Prepare prescription data - merge case data with any overrides from request
    $structuredData = $case['ai_structured_data'] ?? [];
    $patientInfo = $structuredData['patient_info'] ?? [];
    $treatmentPlan = $structuredData['treatment_plan'] ?? [];

    $prescriptionData = [
        'case_id' => $caseId,
        'doctor_id' => $data['doctor_id'] ?? $case['doctor_id'],
        'patient_name' => $data['patient_name'] ?? $patientInfo['name'] ?? $case['patient_name'] ?? '',
        'patient_dob' => $data['patient_dob'] ?? $patientInfo['dob'] ?? $case['patient_dob'],
        'patient_gender' => $data['patient_gender'] ?? $patientInfo['gender'] ?? $case['patient_gender'],
        'diagnosis' => $data['diagnosis'] ?? $structuredData['diagnosis']['primary'] ?? $case['diagnosis'],
        'medications' => $data['medications'] ?? $treatmentPlan['medications'] ?? $case['medications'] ?? [],
        'instructions' => $data['instructions'] ?? $treatmentPlan['lifestyle_recommendations'] ?? null,
        'warnings' => $data['warnings'] ?? implode(', ', $structuredData['warnings'] ?? []),
        'follow_up_date' => $data['follow_up_date'] ?? null
    ];

    // Validate required prescription fields
    if (empty($prescriptionData['patient_name'])) {
        errorResponse('Patient name is required');
    }

    // Ensure medications is an array
    if (is_string($prescriptionData['medications'])) {
        $prescriptionData['medications'] = json_decode($prescriptionData['medications'], true) ?? [];
    }

    // Create or update prescription
    if ($existingPrescription) {
        // Update existing prescription
        $prescriptionModel->update($existingPrescription['id'], $prescriptionData);
        $prescriptionId = $existingPrescription['id'];
        $prescriptionNumber = $existingPrescription['prescription_number'];
        $isNew = false;
    } else {
        // Create new prescription
        $result = $prescriptionService->createPrescription($caseId, $prescriptionData);

        if (!$result['success']) {
            errorResponse('Failed to create prescription: ' . ($result['error'] ?? 'Unknown error'));
        }

        $prescriptionId = $result['prescription_id'];
        $prescriptionNumber = $result['prescription_number'];
        $isNew = true;
    }

    // If sign_immediately is set, sign the prescription
    if (!empty($data['sign_immediately'])) {
        $prescriptionModel->sign($prescriptionId, $prescriptionData['doctor_id']);
    }

    // Update case status
    $caseModel->updateStatus($caseId, 'confirmed');

    // Get the saved prescription
    $savedPrescription = $prescriptionModel->getById($prescriptionId);

    // Prepare response
    successResponse([
        'prescription_id' => $prescriptionId,
        'prescription_number' => $prescriptionNumber,
        'case_id' => $caseId,
        'case_number' => $case['case_number'],
        'is_new' => $isNew,
        'is_signed' => (bool)($savedPrescription['is_signed'] ?? false),
        'prescription' => [
            'patient_name' => $savedPrescription['patient_name'],
            'patient_dob' => $savedPrescription['patient_dob'],
            'patient_gender' => $savedPrescription['patient_gender'],
            'diagnosis' => $savedPrescription['diagnosis'],
            'medications' => $savedPrescription['medications'],
            'instructions' => $savedPrescription['instructions'],
            'warnings' => $savedPrescription['warnings'],
            'follow_up_date' => $savedPrescription['follow_up_date'],
            'doctor_name' => $savedPrescription['doctor_name'] ?? null
        ],
        'queue_status' => 'added'
    ], $isNew ? 'Prescription created and added to queue' : 'Prescription updated');

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
