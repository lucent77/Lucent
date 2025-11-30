<?php
/**
 * Follow-up Question API
 * POST /api/followup.php
 *
 * Generates follow-up questions using Gemini
 * Saves question to database
 * Returns question text
 *
 * Also handles answering follow-up questions
 */

require_once __DIR__ . '/bootstrap.php';

// Allow GET for fetching questions, POST for generating/answering
requireMethod(['GET', 'POST']);

try {
    // Initialize services and models
    $googleService = new GoogleAIService();
    $caseModel = new CaseModel();
    $followupModel = new FollowupQuestionModel();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // GET: Retrieve follow-up questions for a case
        $caseId = (int)getParam('case_id');

        if (!$caseId) {
            errorResponse('case_id is required');
        }

        $unansweredOnly = getParam('unanswered_only', 'false') === 'true';
        $questions = $followupModel->getByCaseId($caseId, $unansweredOnly);

        successResponse([
            'case_id' => $caseId,
            'questions' => $questions,
            'total' => count($questions),
            'unanswered' => count(array_filter($questions, fn($q) => !$q['is_answered']))
        ]);
    }

    // POST: Generate new question or answer existing one
    $data = array_merge($_POST, getJsonBody());

    // Validate case_id
    if (empty($data['case_id'])) {
        errorResponse('case_id is required');
    }

    $caseId = (int)$data['case_id'];

    // Get case data
    $case = $caseModel->getById($caseId);
    if (!$case) {
        errorResponse('Case not found', 404);
    }

    // Check if this is an answer submission
    if (!empty($data['question_id']) && !empty($data['answer'])) {
        // Answer an existing question
        $questionId = (int)$data['question_id'];
        $answerText = $data['answer'];
        $answerAudioId = $data['answer_audio_id'] ?? null;

        // Save the answer
        $followupModel->answer($questionId, $answerText, $answerAudioId);

        // Get the question to know what field it was targeting
        $questions = $followupModel->getByCaseId($caseId);
        $answeredQuestion = null;
        foreach ($questions as $q) {
            if ($q['id'] == $questionId) {
                $answeredQuestion = $q;
                break;
            }
        }

        // If we have the target field, update it in the case
        if ($answeredQuestion && !empty($answeredQuestion['target_field'])) {
            // Update the structured data with the new answer
            $structuredData = $case['ai_structured_data'] ?? [];

            // Use dot notation to set nested field
            $this->setNestedValue($structuredData, $answeredQuestion['target_field'], $answerText);

            $caseModel->update($caseId, ['ai_structured_data' => $structuredData]);
        }

        // Check if there are more unanswered questions
        $nextQuestion = $followupModel->getNextUnanswered($caseId);

        successResponse([
            'case_id' => $caseId,
            'question_id' => $questionId,
            'answer_saved' => true,
            'has_more_questions' => $nextQuestion !== null,
            'next_question' => $nextQuestion
        ], 'Answer saved successfully');
    }

    // Generate a new follow-up question
    // Get the field to ask about
    $targetField = $data['target_field'] ?? null;

    // If no specific field, get from missing fields
    if (!$targetField && !empty($case['missing_fields'])) {
        $missingFields = $case['missing_fields'];
        if (is_string($missingFields)) {
            $missingFields = json_decode($missingFields, true) ?? [];
        }

        // Get critical missing fields first
        $criticalMissing = $missingFields['missing_critical'] ?? [];
        $priorityQuestions = $missingFields['priority_questions'] ?? [];

        if (!empty($priorityQuestions)) {
            $targetField = $priorityQuestions[0]['field'] ?? null;
        } elseif (!empty($criticalMissing)) {
            $targetField = $criticalMissing[0];
        }
    }

    if (!$targetField) {
        // If no missing fields, case might be complete
        successResponse([
            'case_id' => $caseId,
            'complete' => true,
            'message' => 'No missing fields detected. Case data appears complete.'
        ]);
    }

    // Get previously asked questions to avoid repetition
    $existingQuestions = $followupModel->getByCaseId($caseId);
    $previousQuestions = array_map(fn($q) => $q['question_text'], $existingQuestions);

    // Generate follow-up question using Gemini
    $questionResult = $googleService->generateFollowupQuestion(
        $case['ai_structured_data'] ?? [],
        $targetField,
        $previousQuestions,
        $caseId
    );

    if (!$questionResult['success']) {
        errorResponse('Failed to generate question: ' . ($questionResult['error'] ?? 'Unknown error'));
    }

    // Save question to database
    $questionId = $followupModel->create(
        $caseId,
        $questionResult['question'],
        $questionResult['target_field']
    );

    // Increment follow-up count
    $caseModel->incrementFollowupCount($caseId);

    // Update case status
    $caseModel->updateStatus($caseId, 'followup');

    successResponse([
        'case_id' => $caseId,
        'question_id' => $questionId,
        'question' => $questionResult['question'],
        'question_english' => $questionResult['question_english'] ?? null,
        'target_field' => $questionResult['target_field'],
        'expected_answer_type' => $questionResult['expected_answer_type'],
        'processing_time_ms' => $questionResult['processing_time_ms'],
        'followup_count' => $case['followup_count'] + 1
    ], 'Follow-up question generated');

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}

/**
 * Set a nested value using dot notation
 *
 * @param array &$array Array to modify
 * @param string $key Dot notation key
 * @param mixed $value Value to set
 */
function setNestedValue(array &$array, string $key, $value): void
{
    $keys = explode('.', $key);
    $current = &$array;

    foreach ($keys as $i => $k) {
        if ($i === count($keys) - 1) {
            $current[$k] = $value;
        } else {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
    }
}
