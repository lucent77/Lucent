<?php
/**
 * Environment Configuration
 * Voice Prescription System - Hostinger PHP + Google AI
 *
 * IMPORTANT: Update these values with your actual credentials
 * Keep this file secure and never commit to public repositories
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Google Cloud API Configuration
define('GOOGLE_API_KEY', 'your_google_api_key_here');
define('GOOGLE_PROJECT_ID', 'your_project_id_here');
define('GOOGLE_SPEECH_ENDPOINT', 'https://speech.googleapis.com/v1/speech:recognize');
define('GOOGLE_TTS_ENDPOINT', 'https://texttospeech.googleapis.com/v1/text:synthesize');
define('GOOGLE_GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'voice_prescription_db');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'Voice Prescription System');
define('APP_URL', 'https://your-domain.com');
define('APP_DEBUG', false);
define('APP_TIMEZONE', 'Asia/Seoul');

// File Upload Settings
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_AUDIO_TYPES', ['audio/wav', 'audio/mpeg', 'audio/mp3', 'audio/webm', 'audio/ogg']);
define('ALLOWED_EXTENSIONS', ['wav', 'mp3', 'webm', 'ogg']);

// Audio Settings
define('AUDIO_SAMPLE_RATE', 16000);
define('AUDIO_ENCODING', 'LINEAR16');
define('AUDIO_LANGUAGE', 'ko-KR'); // Korean default, can be changed

// TTS Settings
define('TTS_LANGUAGE_CODE', 'ko-KR');
define('TTS_VOICE_NAME', 'ko-KR-Wavenet-A');
define('TTS_AUDIO_ENCODING', 'MP3');

// Session Settings
define('SESSION_LIFETIME', 3600); // 1 hour

// Security Settings
define('CORS_ALLOWED_ORIGINS', ['*']); // Update for production
define('API_RATE_LIMIT', 100); // requests per minute
