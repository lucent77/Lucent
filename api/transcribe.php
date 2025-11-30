<?php
/**
 * Transcription API
 * POST /api/transcribe.php
 *
 * Sends audio to Google Speech-to-Text
 * Returns transcript text
 */

require_once __DIR__ . '/bootstrap.php';

// Require POST method
requireMethod('POST');

try {
    // Get request data
    $data = getJsonBody();

    // Validate required fields
    $missing = validateRequired($data, ['audio_id']);
    if (!empty($missing) && empty($_POST['audio_id'])) {
        // Also check POST data
        $data = $_POST;
        $missing = validateRequired($data, ['audio_id']);
    }

    if (!empty($missing)) {
        errorResponse('Missing required fields: ' . implode(', ', $missing));
    }

    $audioId = (int)($data['audio_id'] ?? $_POST['audio_id']);

    // Initialize services
    $audioService = new AudioService();
    $googleService = new GoogleAIService();
    $caseModel = new CaseModel();

    // Get audio record
    $audio = $audioService->getAudioById($audioId);
    if (!$audio) {
        errorResponse('Audio record not found', 404);
    }

    // Check if file exists
    if (!file_exists($audio['file_path'])) {
        errorResponse('Audio file not found on server', 404);
    }

    // Update audio status to processing
    $audioService->updateStatus($audioId, 'processing');

    // Determine language code
    $languageCode = $data['language'] ?? $audio['language_code'] ?? AUDIO_LANGUAGE;

    // Convert audio if needed (for better recognition)
    $conversionResult = $audioService->convertToWav($audio['file_path']);
    $audioPath = $conversionResult['success'] ? $conversionResult['output_path'] : $audio['file_path'];

    // Perform transcription
    $transcriptionResult = $googleService->speechToText(
        $audioPath,
        $languageCode,
        $audio['case_id']
    );

    if (!$transcriptionResult['success']) {
        $audioService->updateStatus($audioId, 'failed', $transcriptionResult['error']);
        errorResponse('Transcription failed: ' . $transcriptionResult['error']);
    }

    // Update audio record with transcript
    $audioService->updateTranscript(
        $audioId,
        $transcriptionResult['transcript'],
        $transcriptionResult['confidence'] ?? null
    );

    // Update case status
    $caseModel->updateStatus($audio['case_id'], 'analyzing');

    // Clean up temporary converted file
    if ($conversionResult['converted'] && file_exists($conversionResult['output_path'])) {
        unlink($conversionResult['output_path']);
    }

    // Return response
    successResponse([
        'audio_id' => $audioId,
        'case_id' => $audio['case_id'],
        'transcript' => $transcriptionResult['transcript'],
        'confidence' => $transcriptionResult['confidence'],
        'language' => $languageCode,
        'processing_time_ms' => $transcriptionResult['processing_time_ms']
    ], 'Transcription completed successfully');

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
