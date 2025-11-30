<?php
/**
 * Audio Upload API
 * POST /api/upload_audio.php
 *
 * Accepts WAV/MP3/WebM audio files
 * Creates or uses existing case
 * Saves audio to /uploads
 * Returns file path and case_id
 */

require_once __DIR__ . '/bootstrap.php';

// Require POST method
requireMethod('POST');

try {
    // Initialize services
    $audioService = new AudioService();
    $caseModel = new CaseModel();

    // Check if case_id is provided or create new case
    $caseId = null;
    $caseNumber = null;

    if (!empty($_POST['case_id'])) {
        $caseId = (int)$_POST['case_id'];

        // Verify case exists
        $case = $caseModel->getById($caseId);
        if (!$case) {
            errorResponse('Case not found', 404);
        }
        $caseNumber = $case['case_number'];
    } else {
        // Create new case
        $caseData = [
            'doctor_id' => $_POST['doctor_id'] ?? null,
            'patient_name' => $_POST['patient_name'] ?? null,
            'chief_complaint' => $_POST['chief_complaint'] ?? null
        ];

        $result = $caseModel->create($caseData);

        if (!$result['success']) {
            errorResponse('Failed to create case: ' . ($result['error'] ?? 'Unknown error'));
        }

        $caseId = $result['case_id'];
        $caseNumber = $result['case_number'];
    }

    // Determine audio type
    $audioType = $_POST['audio_type'] ?? 'initial';
    $validTypes = ['initial', 'followup', 'clarification', 'final'];
    if (!in_array($audioType, $validTypes)) {
        $audioType = 'initial';
    }

    // Handle file upload vs base64 data
    $uploadResult = null;

    if (!empty($_FILES['audio'])) {
        // Traditional file upload
        $uploadResult = $audioService->uploadFromPost($_FILES['audio'], $caseId, $audioType);
    } elseif (!empty($_POST['audio_data'])) {
        // Base64 encoded audio data
        $mimeType = $_POST['mime_type'] ?? 'audio/webm';
        $uploadResult = $audioService->uploadFromBase64($_POST['audio_data'], $caseId, $audioType, $mimeType);
    } else {
        errorResponse('No audio data provided. Send either "audio" file or "audio_data" base64 string.');
    }

    if (!$uploadResult['success']) {
        errorResponse('Upload failed: ' . ($uploadResult['error'] ?? 'Unknown error'));
    }

    // Update case status to indicate audio was received
    $caseModel->updateStatus($caseId, 'transcribing');

    // Prepare response
    successResponse([
        'case_id' => $caseId,
        'case_number' => $caseNumber,
        'audio_id' => $uploadResult['audio_id'],
        'file_name' => $uploadResult['file_name'],
        'file_path' => $uploadResult['file_path'],
        'file_size' => $uploadResult['file_size'],
        'duration' => $uploadResult['duration'],
        'audio_type' => $audioType
    ], 'Audio uploaded successfully');

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
