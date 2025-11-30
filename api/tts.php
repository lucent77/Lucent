<?php
/**
 * Text-to-Speech API
 * POST /api/tts.php
 *
 * Converts text to speech using Google TTS
 * Saves and returns audio file
 */

require_once __DIR__ . '/bootstrap.php';

// Allow GET for retrieving audio, POST for generating
requireMethod(['GET', 'POST']);

try {
    // Initialize services
    $googleService = new GoogleAIService();
    $followupModel = new FollowupQuestionModel();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // GET: Retrieve existing TTS audio file
        $questionId = (int)getParam('question_id');
        $audioFile = getParam('file');

        if ($questionId) {
            // Get TTS audio for a follow-up question
            $questions = [];
            if ($caseId = getParam('case_id')) {
                $questions = $followupModel->getByCaseId((int)$caseId);
            }

            $question = null;
            foreach ($questions as $q) {
                if ($q['id'] == $questionId) {
                    $question = $q;
                    break;
                }
            }

            if (!$question || empty($question['tts_audio_path'])) {
                errorResponse('TTS audio not found', 404);
            }

            // Serve the audio file
            serveAudioFile($question['tts_audio_path']);
        } elseif ($audioFile) {
            // Direct file access
            $filePath = UPLOAD_DIR . basename($audioFile);
            if (!file_exists($filePath)) {
                errorResponse('Audio file not found', 404);
            }
            serveAudioFile($filePath);
        } else {
            errorResponse('Provide question_id or file parameter');
        }
    }

    // POST: Generate new TTS audio
    $data = array_merge($_POST, getJsonBody());

    // Validate - need either text or question_id
    if (empty($data['text']) && empty($data['question_id'])) {
        errorResponse('Provide either text or question_id');
    }

    $text = '';
    $questionId = null;
    $caseId = $data['case_id'] ?? null;

    if (!empty($data['question_id'])) {
        // Get text from follow-up question
        $questionId = (int)$data['question_id'];

        // We need to get the question - ideally we'd have case_id
        if (!$caseId) {
            errorResponse('case_id required when using question_id');
        }

        $questions = $followupModel->getByCaseId($caseId);
        $question = null;
        foreach ($questions as $q) {
            if ($q['id'] == $questionId) {
                $question = $q;
                break;
            }
        }

        if (!$question) {
            errorResponse('Question not found', 404);
        }

        // Check if TTS already exists
        if (!empty($question['tts_audio_path']) && file_exists($question['tts_audio_path'])) {
            successResponse([
                'question_id' => $questionId,
                'audio_path' => $question['tts_audio_path'],
                'audio_url' => '/api/tts.php?file=' . basename($question['tts_audio_path']),
                'cached' => true
            ], 'TTS audio already exists');
        }

        $text = $question['question_text'];
    } else {
        $text = $data['text'];
    }

    if (empty($text)) {
        errorResponse('No text to convert');
    }

    // Generate unique filename
    $fileName = sprintf(
        'tts_%s_%s.mp3',
        $caseId ?? 'general',
        uniqid()
    );
    $outputPath = UPLOAD_DIR . $fileName;

    // Generate TTS audio
    $ttsResult = $googleService->textToSpeech($text, $outputPath, $caseId);

    if (!$ttsResult['success']) {
        errorResponse('TTS generation failed: ' . ($ttsResult['error'] ?? 'Unknown error'));
    }

    // If this was for a question, update the question record
    if ($questionId) {
        $followupModel->updateTtsAudio($questionId, $outputPath);
    }

    // Generate audio URL
    $audioUrl = '/api/tts.php?file=' . $fileName;

    successResponse([
        'question_id' => $questionId,
        'audio_path' => $outputPath,
        'audio_url' => $audioUrl,
        'file_size' => $ttsResult['file_size'],
        'processing_time_ms' => $ttsResult['processing_time_ms'],
        'text_length' => mb_strlen($text)
    ], 'TTS audio generated successfully');

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}

/**
 * Serve audio file with proper headers
 *
 * @param string $filePath Path to audio file
 */
function serveAudioFile(string $filePath): void
{
    if (!file_exists($filePath)) {
        http_response_code(404);
        exit;
    }

    $mimeType = mime_content_type($filePath) ?: 'audio/mpeg';
    $fileSize = filesize($filePath);

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=3600');

    // Handle range requests for audio streaming
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);

        $start = (int)$matches[1];
        $end = empty($matches[2]) ? $fileSize - 1 : (int)$matches[2];

        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$fileSize");
        header('Content-Length: ' . ($end - $start + 1));

        $fp = fopen($filePath, 'rb');
        fseek($fp, $start);
        echo fread($fp, $end - $start + 1);
        fclose($fp);
    } else {
        readfile($filePath);
    }

    exit;
}
