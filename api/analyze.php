<?php
/**
 * Analysis API
 * POST /api/analyze.php
 *
 * Sends transcript to Google Gemini
 * Returns structured JSON fields
 * Detects missing fields
 */

require_once __DIR__ . '/bootstrap.php';

// Require POST method
requireMethod('POST');

try {
    // Get request data
    $data = array_merge($_POST, getJsonBody());

    // Need either case_id, audio_id, or direct transcript
    if (empty($data['case_id']) && empty($data['audio_id']) && empty($data['transcript'])) {
        errorResponse('Provide case_id, audio_id, or transcript');
    }

    // Initialize services
    $audioService = new AudioService();
    $googleService = new GoogleAIService();
    $prescriptionService = new PrescriptionService();
    $caseModel = new CaseModel();

    $transcript = '';
    $caseId = null;

    // Get transcript from various sources
    if (!empty($data['transcript'])) {
        // Direct transcript provided
        $transcript = $data['transcript'];
        $caseId = $data['case_id'] ?? null;
    } elseif (!empty($data['audio_id'])) {
        // Get transcript from audio record
        $audio = $audioService->getAudioById((int)$data['audio_id']);
        if (!$audio) {
            errorResponse('Audio record not found', 404);
        }
        if (empty($audio['transcript'])) {
            errorResponse('Audio has not been transcribed yet. Call /api/transcribe.php first.');
        }
        $transcript = $audio['transcript'];
        $caseId = $audio['case_id'];
    } elseif (!empty($data['case_id'])) {
        // Get all transcripts for case
        $caseId = (int)$data['case_id'];
        $audios = $audioService->getAudioByCaseId($caseId);

        if (empty($audios)) {
            errorResponse('No audio records found for this case');
        }

        // Combine all transcripts
        $transcripts = [];
        foreach ($audios as $audio) {
            if (!empty($audio['transcript'])) {
                $transcripts[] = $audio['transcript'];
            }
        }

        if (empty($transcripts)) {
            errorResponse('No transcripts available for this case');
        }

        $transcript = implode("\n\n", $transcripts);
    }

    // Verify case exists if provided
    if ($caseId) {
        $case = $caseModel->getById($caseId);
        if (!$case) {
            errorResponse('Case not found', 404);
        }
    }

    // Step 1: Analyze transcript and extract structured data
    $analysisResult = $googleService->analyzeTranscript($transcript, $caseId);

    if (!$analysisResult['success']) {
        errorResponse('Analysis failed: ' . ($analysisResult['error'] ?? 'Unknown error'));
    }

    $structuredData = $analysisResult['structured_data'];

    // Step 2: Detect missing fields
    $missingFieldsResult = $googleService->detectMissingFields($structuredData, $caseId);

    if (!$missingFieldsResult['success']) {
        // Continue even if missing field detection fails
        $missingFieldsResult = [
            'is_complete' => false,
            'completeness_score' => 0,
            'missing_critical' => [],
            'missing_recommended' => [],
            'priority_questions' => []
        ];
    }

    // Step 3: Update case with structured data if case exists
    if ($caseId) {
        $caseModel->updateStructuredData($caseId, $structuredData, $missingFieldsResult);

        // Save prescription fields
        $prescriptionService->savePrescriptionFields($caseId, $structuredData);

        // Update case status based on completeness
        if ($missingFieldsResult['is_complete']) {
            $caseModel->updateStatus($caseId, 'pending_confirmation');
        } else {
            $caseModel->updateStatus($caseId, 'followup');
        }
    }

    // Build response
    $response = [
        'case_id' => $caseId,
        'structured_data' => $structuredData,
        'analysis' => [
            'is_complete' => $missingFieldsResult['is_complete'],
            'completeness_score' => $missingFieldsResult['completeness_score'],
            'missing_critical' => $missingFieldsResult['missing_critical'],
            'missing_recommended' => $missingFieldsResult['missing_recommended'],
            'priority_questions' => $missingFieldsResult['priority_questions']
        ],
        'processing_time_ms' => $analysisResult['processing_time_ms'] + ($missingFieldsResult['processing_time_ms'] ?? 0),
        'next_action' => $missingFieldsResult['is_complete'] ? 'confirm' : 'followup'
    ];

    // If follow-up needed, include the first question suggestion
    if (!$missingFieldsResult['is_complete'] && !empty($missingFieldsResult['priority_questions'])) {
        $response['suggested_question'] = $missingFieldsResult['priority_questions'][0];
    }

    successResponse($response, 'Analysis completed successfully');

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
