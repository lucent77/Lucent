<?php
/**
 * Google AI Service
 * Handles all Google Cloud API integrations:
 * - Speech-to-Text
 * - Gemini AI (Analysis, Follow-up, Summary)
 * - Text-to-Speech
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/config/database.php';

class GoogleAIService
{
    private string $apiKey;
    private string $projectId;

    // AI Prompt Templates
    private array $promptTemplates;

    public function __construct()
    {
        $this->apiKey = GOOGLE_API_KEY;
        $this->projectId = GOOGLE_PROJECT_ID;
        $this->initializePromptTemplates();
    }

    /**
     * Initialize AI Prompt Templates
     */
    private function initializePromptTemplates(): void
    {
        $this->promptTemplates = [
            // Case Structuring Prompt
            'case_structuring' => <<<PROMPT
You are a medical AI assistant specializing in extracting structured data from doctor-patient consultation transcripts.

Analyze the following transcript and extract all relevant medical information into a structured JSON format.

TRANSCRIPT:
{transcript}

Extract and return a JSON object with these fields (use null for missing information):
{
    "patient_info": {
        "name": "string or null",
        "age": "number or null",
        "gender": "M/F/O or null",
        "phone": "string or null",
        "id_number": "string or null"
    },
    "chief_complaint": "main reason for visit",
    "symptoms": ["list", "of", "symptoms"],
    "symptom_duration": "how long symptoms have been present",
    "symptom_severity": "mild/moderate/severe or description",
    "medical_history": ["relevant", "past", "conditions"],
    "current_medications": ["existing", "medications"],
    "allergies": ["known", "allergies"],
    "vital_signs": {
        "blood_pressure": "string or null",
        "heart_rate": "number or null",
        "temperature": "number or null",
        "respiratory_rate": "number or null",
        "oxygen_saturation": "number or null"
    },
    "physical_examination": "examination findings",
    "diagnosis": {
        "primary": "main diagnosis",
        "secondary": ["other", "diagnoses"],
        "differential": ["possible", "alternatives"]
    },
    "treatment_plan": {
        "medications": [
            {
                "name": "medication name",
                "dosage": "dosage amount",
                "frequency": "how often",
                "duration": "how long",
                "route": "oral/topical/injection/etc",
                "instructions": "special instructions"
            }
        ],
        "procedures": ["any", "procedures"],
        "lifestyle_recommendations": ["recommendations"],
        "follow_up": "follow-up instructions"
    },
    "warnings": ["important", "warnings"],
    "notes": "additional doctor notes"
}

IMPORTANT:
- Extract only information explicitly mentioned in the transcript
- Use null for fields not mentioned
- Maintain medical accuracy
- Return ONLY valid JSON, no additional text

JSON Response:
PROMPT,

            // Missing Field Detection Prompt
            'missing_field_detection' => <<<PROMPT
You are a medical prescription validation AI.

Review the following extracted medical data and identify any CRITICAL missing fields that are required for a complete and safe prescription.

EXTRACTED DATA:
{structured_data}

REQUIRED FIELDS FOR PRESCRIPTION:
1. Patient identification (name at minimum)
2. Chief complaint / reason for visit
3. At least one symptom or finding
4. Diagnosis (primary)
5. At least one medication with: name, dosage, frequency, duration
6. Any known allergies (even if "none known")

Analyze and return a JSON object:
{
    "is_complete": true/false,
    "completeness_score": 0-100,
    "missing_critical": ["list of critical missing fields"],
    "missing_recommended": ["list of recommended but optional fields"],
    "validation_notes": ["any concerns or notes"],
    "priority_questions": [
        {
            "field": "field_name",
            "priority": "critical/high/medium",
            "suggested_question": "question to ask patient/doctor"
        }
    ]
}

Return ONLY valid JSON.
PROMPT,

            // Follow-up Question Generation Prompt
            'followup_question' => <<<PROMPT
You are a medical AI assistant helping to gather missing information for a prescription.

CONTEXT:
- Current case data: {case_data}
- Missing field to ask about: {missing_field}
- Previous questions asked: {previous_questions}

Generate a natural, professional question in Korean to ask the doctor or patient to obtain the missing information.

Requirements:
1. Be polite and professional (Korean formal speech)
2. Be specific about what information is needed
3. Provide context if helpful
4. Keep it concise (1-2 sentences)
5. Do not repeat previous questions

Return JSON:
{
    "question_korean": "질문 내용",
    "question_english": "English translation",
    "target_field": "field being asked about",
    "expected_answer_type": "text/number/date/selection",
    "context_hint": "optional context for the question"
}

Return ONLY valid JSON.
PROMPT,

            // Final Summary Prompt
            'final_summary' => <<<PROMPT
You are a medical documentation AI creating a final prescription summary.

COMPLETE CASE DATA:
{complete_data}

Generate a professional medical prescription summary in Korean with English terms where appropriate for medications.

Return JSON:
{
    "summary_korean": "환자 요약 정보",
    "prescription_text": "처방전 텍스트 (formatted)",
    "medication_instructions": [
        {
            "medication": "약품명",
            "korean_instructions": "복용 지시사항",
            "english_instructions": "Dosage instructions"
        }
    ],
    "warnings_korean": ["주의사항"],
    "follow_up_korean": "다음 방문 안내",
    "formatted_prescription": "Full formatted prescription text for printing"
}

Return ONLY valid JSON.
PROMPT,

            // Medication Safety Check Prompt
            'medication_safety' => <<<PROMPT
You are a pharmacology AI checking medication safety.

PATIENT INFO:
{patient_info}

PRESCRIBED MEDICATIONS:
{medications}

KNOWN ALLERGIES:
{allergies}

Check for:
1. Drug-drug interactions
2. Allergy conflicts
3. Dosage appropriateness
4. Contraindications based on patient info

Return JSON:
{
    "is_safe": true/false,
    "alerts": [
        {
            "severity": "critical/warning/info",
            "type": "interaction/allergy/dosage/contraindication",
            "message": "description",
            "medications_involved": ["med1", "med2"],
            "recommendation": "suggested action"
        }
    ],
    "verified": true/false
}

Return ONLY valid JSON.
PROMPT
        ];
    }

    /**
     * Get prompt template with variable substitution
     */
    public function getPromptTemplate(string $templateName, array $variables = []): string
    {
        if (!isset($this->promptTemplates[$templateName])) {
            throw new InvalidArgumentException("Unknown prompt template: $templateName");
        }

        $prompt = $this->promptTemplates[$templateName];

        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            $prompt = str_replace("{{$key}}", $value, $prompt);
        }

        return $prompt;
    }

    /**
     * Speech-to-Text: Transcribe audio file
     *
     * @param string $audioFilePath Path to audio file
     * @param string $languageCode Language code (default: ko-KR)
     * @param int|null $caseId Optional case ID for logging
     * @return array Transcription result
     */
    public function speechToText(string $audioFilePath, string $languageCode = 'ko-KR', ?int $caseId = null): array
    {
        $startTime = microtime(true);

        try {
            // Read and encode audio file
            if (!file_exists($audioFilePath)) {
                throw new Exception("Audio file not found: $audioFilePath");
            }

            $audioContent = base64_encode(file_get_contents($audioFilePath));
            $mimeType = mime_content_type($audioFilePath);

            // Determine encoding based on file type
            $encoding = $this->getAudioEncoding($mimeType);

            // Build request payload
            $requestPayload = [
                'config' => [
                    'encoding' => $encoding,
                    'sampleRateHertz' => AUDIO_SAMPLE_RATE,
                    'languageCode' => $languageCode,
                    'enableAutomaticPunctuation' => true,
                    'enableWordTimeOffsets' => false,
                    'model' => 'latest_long',
                    'useEnhanced' => true
                ],
                'audio' => [
                    'content' => $audioContent
                ]
            ];

            // Make API request
            $endpoint = GOOGLE_SPEECH_ENDPOINT . '?key=' . $this->apiKey;
            $response = $this->makeCurlRequest($endpoint, $requestPayload);

            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            // Parse response
            if (isset($response['results'])) {
                $transcript = '';
                $confidence = 0;
                $wordCount = 0;

                foreach ($response['results'] as $result) {
                    if (isset($result['alternatives'][0])) {
                        $alternative = $result['alternatives'][0];
                        $transcript .= $alternative['transcript'] . ' ';
                        if (isset($alternative['confidence'])) {
                            $confidence += $alternative['confidence'];
                            $wordCount++;
                        }
                    }
                }

                $avgConfidence = $wordCount > 0 ? $confidence / $wordCount : 0;

                $result = [
                    'success' => true,
                    'transcript' => trim($transcript),
                    'confidence' => round($avgConfidence, 4),
                    'language' => $languageCode,
                    'processing_time_ms' => $processingTime
                ];

                // Log successful request
                $this->logAIRequest($caseId, 'speech_to_text', $requestPayload, $response, 'success', $processingTime);

                return $result;
            }

            throw new Exception('No transcription results returned');
        } catch (Exception $e) {
            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            // Log failed request
            $this->logAIRequest(
                $caseId,
                'speech_to_text',
                $requestPayload ?? [],
                ['error' => $e->getMessage()],
                'error',
                $processingTime,
                $e->getMessage()
            );

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime
            ];
        }
    }

    /**
     * Gemini AI: Analyze transcript and extract structured data
     *
     * @param string $transcript Transcribed text
     * @param int|null $caseId Optional case ID for logging
     * @return array Analysis result
     */
    public function analyzeTranscript(string $transcript, ?int $caseId = null): array
    {
        $startTime = microtime(true);

        try {
            $prompt = $this->getPromptTemplate('case_structuring', [
                'transcript' => $transcript
            ]);

            $response = $this->callGemini($prompt, $caseId, 'gemini_analyze');
            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            if ($response['success']) {
                // Parse the structured data from Gemini response
                $structuredData = $this->extractJsonFromResponse($response['text']);

                return [
                    'success' => true,
                    'structured_data' => $structuredData,
                    'raw_response' => $response['text'],
                    'processing_time_ms' => $processingTime
                ];
            }

            throw new Exception($response['error'] ?? 'Gemini analysis failed');
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => (int)((microtime(true) - $startTime) * 1000)
            ];
        }
    }

    /**
     * Gemini AI: Detect missing fields
     *
     * @param array $structuredData Structured case data
     * @param int|null $caseId Optional case ID
     * @return array Missing fields analysis
     */
    public function detectMissingFields(array $structuredData, ?int $caseId = null): array
    {
        $startTime = microtime(true);

        try {
            $prompt = $this->getPromptTemplate('missing_field_detection', [
                'structured_data' => $structuredData
            ]);

            $response = $this->callGemini($prompt, $caseId, 'gemini_analyze');
            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            if ($response['success']) {
                $missingFieldsData = $this->extractJsonFromResponse($response['text']);

                return [
                    'success' => true,
                    'is_complete' => $missingFieldsData['is_complete'] ?? false,
                    'completeness_score' => $missingFieldsData['completeness_score'] ?? 0,
                    'missing_critical' => $missingFieldsData['missing_critical'] ?? [],
                    'missing_recommended' => $missingFieldsData['missing_recommended'] ?? [],
                    'priority_questions' => $missingFieldsData['priority_questions'] ?? [],
                    'processing_time_ms' => $processingTime
                ];
            }

            throw new Exception($response['error'] ?? 'Missing field detection failed');
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => (int)((microtime(true) - $startTime) * 1000)
            ];
        }
    }

    /**
     * Gemini AI: Generate follow-up question
     *
     * @param array $caseData Current case data
     * @param string $missingField Field to ask about
     * @param array $previousQuestions Previously asked questions
     * @param int|null $caseId Optional case ID
     * @return array Follow-up question
     */
    public function generateFollowupQuestion(
        array $caseData,
        string $missingField,
        array $previousQuestions = [],
        ?int $caseId = null
    ): array {
        $startTime = microtime(true);

        try {
            $prompt = $this->getPromptTemplate('followup_question', [
                'case_data' => $caseData,
                'missing_field' => $missingField,
                'previous_questions' => $previousQuestions
            ]);

            $response = $this->callGemini($prompt, $caseId, 'gemini_followup');
            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            if ($response['success']) {
                $questionData = $this->extractJsonFromResponse($response['text']);

                return [
                    'success' => true,
                    'question' => $questionData['question_korean'] ?? '',
                    'question_english' => $questionData['question_english'] ?? '',
                    'target_field' => $questionData['target_field'] ?? $missingField,
                    'expected_answer_type' => $questionData['expected_answer_type'] ?? 'text',
                    'processing_time_ms' => $processingTime
                ];
            }

            throw new Exception($response['error'] ?? 'Follow-up question generation failed');
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => (int)((microtime(true) - $startTime) * 1000)
            ];
        }
    }

    /**
     * Gemini AI: Generate final summary
     *
     * @param array $completeData Complete case data
     * @param int|null $caseId Optional case ID
     * @return array Final summary
     */
    public function generateFinalSummary(array $completeData, ?int $caseId = null): array
    {
        $startTime = microtime(true);

        try {
            $prompt = $this->getPromptTemplate('final_summary', [
                'complete_data' => $completeData
            ]);

            $response = $this->callGemini($prompt, $caseId, 'gemini_summary');
            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            if ($response['success']) {
                $summaryData = $this->extractJsonFromResponse($response['text']);

                return [
                    'success' => true,
                    'summary' => $summaryData,
                    'processing_time_ms' => $processingTime
                ];
            }

            throw new Exception($response['error'] ?? 'Summary generation failed');
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => (int)((microtime(true) - $startTime) * 1000)
            ];
        }
    }

    /**
     * Text-to-Speech: Convert text to audio
     *
     * @param string $text Text to convert
     * @param string $outputPath Output file path
     * @param int|null $caseId Optional case ID
     * @return array TTS result
     */
    public function textToSpeech(string $text, string $outputPath, ?int $caseId = null): array
    {
        $startTime = microtime(true);

        try {
            $requestPayload = [
                'input' => [
                    'text' => $text
                ],
                'voice' => [
                    'languageCode' => TTS_LANGUAGE_CODE,
                    'name' => TTS_VOICE_NAME,
                    'ssmlGender' => 'FEMALE'
                ],
                'audioConfig' => [
                    'audioEncoding' => TTS_AUDIO_ENCODING,
                    'speakingRate' => 1.0,
                    'pitch' => 0
                ]
            ];

            $endpoint = GOOGLE_TTS_ENDPOINT . '?key=' . $this->apiKey;
            $response = $this->makeCurlRequest($endpoint, $requestPayload);

            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            if (isset($response['audioContent'])) {
                // Decode and save audio
                $audioData = base64_decode($response['audioContent']);
                file_put_contents($outputPath, $audioData);

                // Log successful request
                $this->logAIRequest($caseId, 'text_to_speech', $requestPayload, ['output_path' => $outputPath], 'success', $processingTime);

                return [
                    'success' => true,
                    'audio_path' => $outputPath,
                    'file_size' => strlen($audioData),
                    'processing_time_ms' => $processingTime
                ];
            }

            throw new Exception('No audio content in response');
        } catch (Exception $e) {
            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            $this->logAIRequest(
                $caseId,
                'text_to_speech',
                $requestPayload ?? [],
                ['error' => $e->getMessage()],
                'error',
                $processingTime,
                $e->getMessage()
            );

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime
            ];
        }
    }

    /**
     * Call Gemini API
     *
     * @param string $prompt The prompt to send
     * @param int|null $caseId Optional case ID for logging
     * @param string $serviceType Service type for logging
     * @return array API response
     */
    private function callGemini(string $prompt, ?int $caseId = null, string $serviceType = 'gemini_analyze'): array
    {
        $startTime = microtime(true);

        try {
            $requestPayload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 8192
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_ONLY_HIGH'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_ONLY_HIGH'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_ONLY_HIGH'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_ONLY_HIGH'
                    ]
                ]
            ];

            $endpoint = GOOGLE_GEMINI_ENDPOINT . '?key=' . $this->apiKey;
            $response = $this->makeCurlRequest($endpoint, $requestPayload);

            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $responseText = $response['candidates'][0]['content']['parts'][0]['text'];
                $tokensUsed = $response['usageMetadata']['totalTokenCount'] ?? null;

                // Log successful request
                $this->logAIRequest(
                    $caseId,
                    $serviceType,
                    ['prompt_length' => strlen($prompt)],
                    ['response_length' => strlen($responseText)],
                    'success',
                    $processingTime,
                    null,
                    $tokensUsed
                );

                return [
                    'success' => true,
                    'text' => $responseText,
                    'tokens_used' => $tokensUsed,
                    'processing_time_ms' => $processingTime
                ];
            }

            // Check for blocked content
            if (isset($response['promptFeedback']['blockReason'])) {
                throw new Exception('Content blocked: ' . $response['promptFeedback']['blockReason']);
            }

            throw new Exception('Invalid Gemini response structure');
        } catch (Exception $e) {
            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            $this->logAIRequest(
                $caseId,
                $serviceType,
                ['prompt_length' => strlen($prompt)],
                ['error' => $e->getMessage()],
                'error',
                $processingTime,
                $e->getMessage()
            );

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime
            ];
        }
    }

    /**
     * Make cURL request to Google API
     *
     * @param string $endpoint API endpoint
     * @param array $payload Request payload
     * @return array Response
     */
    private function makeCurlRequest(string $endpoint, array $payload): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: $error");
        }

        if ($httpCode >= 400) {
            $errorResponse = json_decode($response, true);
            $errorMessage = $errorResponse['error']['message'] ?? "HTTP error: $httpCode";
            throw new Exception($errorMessage);
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Extract JSON from AI response text
     *
     * @param string $responseText Response text that may contain JSON
     * @return array Parsed JSON
     */
    private function extractJsonFromResponse(string $responseText): array
    {
        // Try direct JSON parse first
        $decoded = json_decode($responseText, true);
        if ($decoded !== null) {
            return $decoded;
        }

        // Try to extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $responseText, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Try to find JSON object in response
        if (preg_match('/\{[\s\S]*\}/', $responseText, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        throw new Exception('Failed to parse JSON from response');
    }

    /**
     * Get audio encoding type from MIME type
     *
     * @param string $mimeType MIME type
     * @return string Google encoding type
     */
    private function getAudioEncoding(string $mimeType): string
    {
        $encodingMap = [
            'audio/wav' => 'LINEAR16',
            'audio/x-wav' => 'LINEAR16',
            'audio/mp3' => 'MP3',
            'audio/mpeg' => 'MP3',
            'audio/ogg' => 'OGG_OPUS',
            'audio/webm' => 'WEBM_OPUS',
            'audio/flac' => 'FLAC'
        ];

        return $encodingMap[$mimeType] ?? 'LINEAR16';
    }

    /**
     * Log AI API request to database
     */
    private function logAIRequest(
        ?int $caseId,
        string $serviceType,
        array $requestPayload,
        array $responsePayload,
        string $status,
        int $processingTime,
        ?string $errorMessage = null,
        ?int $tokensUsed = null
    ): void {
        try {
            $sql = "INSERT INTO ai_logs
                    (case_id, service_type, request_payload, response_payload, status,
                     processing_time_ms, error_message, tokens_used, api_endpoint)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $endpoint = match ($serviceType) {
                'speech_to_text' => GOOGLE_SPEECH_ENDPOINT,
                'text_to_speech' => GOOGLE_TTS_ENDPOINT,
                default => GOOGLE_GEMINI_ENDPOINT
            };

            Database::execute($sql, [
                $caseId,
                $serviceType,
                json_encode($requestPayload, JSON_UNESCAPED_UNICODE),
                json_encode($responsePayload, JSON_UNESCAPED_UNICODE),
                $status,
                $processingTime,
                $errorMessage,
                $tokensUsed,
                $endpoint
            ]);
        } catch (Exception $e) {
            // Silently fail logging - don't break main functionality
            error_log("AI Log Error: " . $e->getMessage());
        }
    }

    /**
     * Get all prompt templates (for debugging/admin)
     */
    public function getPromptTemplates(): array
    {
        return array_keys($this->promptTemplates);
    }
}
