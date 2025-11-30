<?php
/**
 * Audio Service
 * Handles audio file upload, validation, and processing
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/config/database.php';

class AudioService
{
    private string $uploadDir;
    private int $maxFileSize;
    private array $allowedTypes;
    private array $allowedExtensions;

    public function __construct()
    {
        $this->uploadDir = UPLOAD_DIR;
        $this->maxFileSize = MAX_UPLOAD_SIZE;
        $this->allowedTypes = ALLOWED_AUDIO_TYPES;
        $this->allowedExtensions = ALLOWED_EXTENSIONS;

        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload audio file from POST request
     *
     * @param array $file $_FILES array element
     * @param int $caseId Case ID to associate with
     * @param string $audioType Type of audio (initial, followup, etc.)
     * @return array Upload result
     */
    public function uploadFromPost(array $file, int $caseId, string $audioType = 'initial'): array
    {
        try {
            // Validate file upload
            $this->validateUpload($file);

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = sprintf(
                'case_%d_%s_%s.%s',
                $caseId,
                $audioType,
                uniqid(),
                $extension
            );

            $filePath = $this->uploadDir . $fileName;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception('Failed to move uploaded file');
            }

            // Get audio metadata
            $metadata = $this->getAudioMetadata($filePath);

            // Save to database
            $audioId = $this->saveAudioRecord($caseId, [
                'audio_type' => $audioType,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'duration_seconds' => $metadata['duration'] ?? null,
                'sample_rate' => $metadata['sample_rate'] ?? null
            ]);

            return [
                'success' => true,
                'audio_id' => $audioId,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $file['size'],
                'duration' => $metadata['duration'] ?? null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload audio from base64 encoded data
     *
     * @param string $base64Data Base64 encoded audio data
     * @param int $caseId Case ID to associate with
     * @param string $audioType Type of audio
     * @param string $mimeType MIME type of audio
     * @return array Upload result
     */
    public function uploadFromBase64(
        string $base64Data,
        int $caseId,
        string $audioType = 'initial',
        string $mimeType = 'audio/webm'
    ): array {
        try {
            // Remove data URL prefix if present
            if (preg_match('/^data:audio\/\w+;base64,/', $base64Data)) {
                $base64Data = preg_replace('/^data:audio\/\w+;base64,/', '', $base64Data);
            }

            // Decode base64 data
            $audioData = base64_decode($base64Data);
            if ($audioData === false) {
                throw new Exception('Invalid base64 audio data');
            }

            // Validate size
            $fileSize = strlen($audioData);
            if ($fileSize > $this->maxFileSize) {
                throw new Exception('File size exceeds limit');
            }

            // Determine extension from MIME type
            $extension = $this->getExtensionFromMimeType($mimeType);

            // Generate unique filename
            $fileName = sprintf(
                'case_%d_%s_%s.%s',
                $caseId,
                $audioType,
                uniqid(),
                $extension
            );

            $filePath = $this->uploadDir . $fileName;

            // Write audio data to file
            if (file_put_contents($filePath, $audioData) === false) {
                throw new Exception('Failed to save audio file');
            }

            // Get audio metadata
            $metadata = $this->getAudioMetadata($filePath);

            // Save to database
            $audioId = $this->saveAudioRecord($caseId, [
                'audio_type' => $audioType,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'duration_seconds' => $metadata['duration'] ?? null,
                'sample_rate' => $metadata['sample_rate'] ?? null
            ]);

            return [
                'success' => true,
                'audio_id' => $audioId,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'duration' => $metadata['duration'] ?? null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get audio file by ID
     *
     * @param int $audioId Audio record ID
     * @return array|null Audio record
     */
    public function getAudioById(int $audioId): ?array
    {
        $sql = "SELECT * FROM case_audio WHERE id = ?";
        $stmt = Database::execute($sql, [$audioId]);
        $record = $stmt->fetch();

        return $record ?: null;
    }

    /**
     * Get all audio files for a case
     *
     * @param int $caseId Case ID
     * @return array Audio records
     */
    public function getAudioByCaseId(int $caseId): array
    {
        $sql = "SELECT * FROM case_audio WHERE case_id = ? ORDER BY created_at ASC";
        $stmt = Database::execute($sql, [$caseId]);

        return $stmt->fetchAll();
    }

    /**
     * Update audio transcript
     *
     * @param int $audioId Audio ID
     * @param string $transcript Transcript text
     * @param float|null $confidence Confidence score
     * @return bool Success
     */
    public function updateTranscript(int $audioId, string $transcript, ?float $confidence = null): bool
    {
        $sql = "UPDATE case_audio
                SET transcript = ?,
                    transcript_confidence = ?,
                    processing_status = 'transcribed',
                    processed_at = NOW()
                WHERE id = ?";

        Database::execute($sql, [$transcript, $confidence, $audioId]);

        return true;
    }

    /**
     * Update audio processing status
     *
     * @param int $audioId Audio ID
     * @param string $status Processing status
     * @param string|null $errorMessage Error message if failed
     * @return bool Success
     */
    public function updateStatus(int $audioId, string $status, ?string $errorMessage = null): bool
    {
        $sql = "UPDATE case_audio
                SET processing_status = ?,
                    error_message = ?,
                    processed_at = CASE WHEN ? IN ('transcribed', 'failed') THEN NOW() ELSE processed_at END
                WHERE id = ?";

        Database::execute($sql, [$status, $errorMessage, $status, $audioId]);

        return true;
    }

    /**
     * Delete audio file
     *
     * @param int $audioId Audio ID
     * @return bool Success
     */
    public function deleteAudio(int $audioId): bool
    {
        // Get file path first
        $audio = $this->getAudioById($audioId);
        if (!$audio) {
            return false;
        }

        // Delete file from disk
        if (file_exists($audio['file_path'])) {
            unlink($audio['file_path']);
        }

        // Delete database record
        $sql = "DELETE FROM case_audio WHERE id = ?";
        Database::execute($sql, [$audioId]);

        return true;
    }

    /**
     * Convert audio to format suitable for Google Speech-to-Text
     * Requires ffmpeg to be installed on server
     *
     * @param string $inputPath Input file path
     * @param string|null $outputPath Output file path (optional)
     * @return array Conversion result
     */
    public function convertToWav(string $inputPath, ?string $outputPath = null): array
    {
        try {
            if (!file_exists($inputPath)) {
                throw new Exception('Input file not found');
            }

            if ($outputPath === null) {
                $outputPath = preg_replace('/\.\w+$/', '_converted.wav', $inputPath);
            }

            // Use ffmpeg to convert to WAV format suitable for Google Speech-to-Text
            $command = sprintf(
                'ffmpeg -i %s -ar 16000 -ac 1 -f wav %s -y 2>&1',
                escapeshellarg($inputPath),
                escapeshellarg($outputPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                // If ffmpeg fails, try using the original file
                return [
                    'success' => true,
                    'converted' => false,
                    'output_path' => $inputPath,
                    'message' => 'Using original file (ffmpeg not available or conversion failed)'
                ];
            }

            return [
                'success' => true,
                'converted' => true,
                'output_path' => $outputPath,
                'original_path' => $inputPath
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate file upload
     *
     * @param array $file $_FILES array element
     * @throws Exception If validation fails
     */
    private function validateUpload(array $file): void
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
            ];

            throw new Exception($errorMessages[$file['error']] ?? 'Unknown upload error');
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds ' . ($this->maxFileSize / 1024 / 1024) . 'MB limit');
        }

        // Check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('Invalid audio file type: ' . $mimeType);
        }

        // Check extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('Invalid file extension: ' . $extension);
        }
    }

    /**
     * Get audio file metadata
     *
     * @param string $filePath File path
     * @return array Metadata
     */
    private function getAudioMetadata(string $filePath): array
    {
        $metadata = [
            'duration' => null,
            'sample_rate' => null
        ];

        // Try to get duration using ffprobe
        $command = sprintf(
            'ffprobe -v quiet -show_entries format=duration -of csv=p=0 %s 2>/dev/null',
            escapeshellarg($filePath)
        );

        $duration = exec($command);
        if (is_numeric($duration)) {
            $metadata['duration'] = (float)$duration;
        }

        // Try to get sample rate
        $command = sprintf(
            'ffprobe -v quiet -show_entries stream=sample_rate -of csv=p=0 %s 2>/dev/null',
            escapeshellarg($filePath)
        );

        $sampleRate = exec($command);
        if (is_numeric($sampleRate)) {
            $metadata['sample_rate'] = (int)$sampleRate;
        }

        return $metadata;
    }

    /**
     * Get file extension from MIME type
     *
     * @param string $mimeType MIME type
     * @return string Extension
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $mimeMap = [
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
            'audio/mp3' => 'mp3',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/webm' => 'webm',
            'audio/flac' => 'flac'
        ];

        return $mimeMap[$mimeType] ?? 'webm';
    }

    /**
     * Save audio record to database
     *
     * @param int $caseId Case ID
     * @param array $data Audio data
     * @return int Inserted ID
     */
    private function saveAudioRecord(int $caseId, array $data): int
    {
        $sql = "INSERT INTO case_audio
                (case_id, audio_type, file_name, file_path, file_size, mime_type,
                 duration_seconds, sample_rate, processing_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'uploaded')";

        Database::execute($sql, [
            $caseId,
            $data['audio_type'],
            $data['file_name'],
            $data['file_path'],
            $data['file_size'],
            $data['mime_type'],
            $data['duration_seconds'],
            $data['sample_rate']
        ]);

        return (int)Database::lastInsertId();
    }

    /**
     * Clean up old temporary files
     *
     * @param int $hoursOld Files older than this will be deleted
     * @return int Number of files deleted
     */
    public function cleanupOldFiles(int $hoursOld = 24): int
    {
        $deleted = 0;
        $cutoffTime = time() - ($hoursOld * 3600);

        $files = glob($this->uploadDir . '*');
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
