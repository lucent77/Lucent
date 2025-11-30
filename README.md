# Voice Prescription System

AI-powered voice-to-prescription workflow using Google Cloud APIs.

## Features

- **Voice Recording**: Browser-based audio recording for doctor-patient consultations
- **Speech-to-Text**: Google Cloud Speech-to-Text API integration for transcription
- **AI Analysis**: Google Gemini AI for extracting structured prescription data
- **Missing Field Detection**: Intelligent detection of missing required information
- **Follow-up Questions**: AI-generated questions with Text-to-Speech audio
- **Doctor Confirmation UI**: Review and edit extracted data before saving
- **Front Desk Dashboard**: Prescription queue management for pharmacists

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: HTML5, Bootstrap 5, JavaScript (ES6+)
- **APIs**: Google Cloud Speech-to-Text, Gemini AI, Text-to-Speech

## Project Structure

```
/public
   index.php          # Main landing page
   record.html        # Voice recording interface
   confirm.html       # Prescription confirmation page
   frontdesk.html     # Front desk dashboard

/api
   bootstrap.php      # Common API initialization
   upload_audio.php   # Audio file upload endpoint
   transcribe.php     # Speech-to-Text endpoint
   analyze.php        # Gemini analysis endpoint
   followup.php       # Follow-up question endpoint
   tts.php            # Text-to-Speech endpoint
   save_prescription.php  # Save prescription endpoint
   cases.php          # Case management endpoint
   queue.php          # Queue management endpoint
   doctors.php        # Doctor data endpoint

/services
   GoogleAIService.php    # Google Cloud API wrapper
   AudioService.php       # Audio file handling
   PrescriptionService.php # Prescription business logic

/models
   CaseModel.php          # Case data model
   PrescriptionModel.php  # Prescription data model

/config
   database.php       # Database connection
   env.php            # Environment configuration

/database
   schema.sql         # MySQL database schema

/assets/js
   recorder.js        # Audio recording module
   ui.js              # UI controller module

/uploads             # Audio file storage
```

## Installation

### 1. Prerequisites

- PHP 7.4 or higher with extensions: PDO, PDO_MySQL, cURL, JSON, mbstring
- MySQL 5.7+ or MariaDB 10.2+
- Google Cloud account with enabled APIs:
  - Cloud Speech-to-Text API
  - Generative Language API (Gemini)
  - Cloud Text-to-Speech API

### 2. Database Setup

1. Create a MySQL database:
   ```sql
   CREATE DATABASE voice_prescription_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Import the schema:
   ```bash
   mysql -u username -p voice_prescription_db < database/schema.sql
   ```

### 3. Configuration

1. Edit `/config/env.php` with your credentials:
   ```php
   define('GOOGLE_API_KEY', 'your_google_api_key');
   define('GOOGLE_PROJECT_ID', 'your_project_id');
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_username');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'voice_prescription_db');
   ```

### 4. File Permissions

```bash
chmod 755 /uploads
chmod 644 /config/env.php
```

### 5. Hostinger Deployment

1. Upload all files to your Hostinger public_html directory
2. Configure the database in Hostinger's control panel
3. Update `/config/env.php` with Hostinger MySQL credentials
4. Ensure the `/uploads` directory is writable

## API Endpoints

### POST /api/upload_audio.php
Upload audio file for processing.

**Parameters:**
- `audio` (file) or `audio_data` (base64): Audio content
- `case_id` (optional): Existing case ID
- `doctor_id` (optional): Doctor ID
- `audio_type`: initial/followup/clarification

### POST /api/transcribe.php
Transcribe uploaded audio.

**Parameters:**
- `audio_id`: Audio record ID
- `language` (optional): Language code (default: ko-KR)

### POST /api/analyze.php
Analyze transcript and extract structured data.

**Parameters:**
- `case_id` or `audio_id` or `transcript`: Source of text

### POST /api/followup.php
Generate or answer follow-up questions.

**Parameters:**
- `case_id`: Case ID
- `target_field` (optional): Specific field to ask about
- `question_id` + `answer`: For answering questions

### POST /api/tts.php
Generate Text-to-Speech audio.

**Parameters:**
- `case_id`: Case ID
- `question_id` or `text`: Content to synthesize

### POST /api/save_prescription.php
Save finalized prescription.

**Parameters:**
- `case_id`: Case ID
- Prescription data overrides (optional)

## Workflow

1. **Record**: Doctor records consultation using browser microphone
2. **Upload**: Audio is uploaded and stored on server
3. **Transcribe**: Google Speech-to-Text converts audio to text
4. **Analyze**: Gemini AI extracts structured prescription data
5. **Follow-up**: If data is incomplete, AI generates questions
6. **Confirm**: Doctor reviews and edits extracted data
7. **Save**: Prescription is saved and added to front desk queue
8. **Dispense**: Front desk processes and dispenses medications

## Security Considerations

- Never commit `env.php` with real credentials to version control
- Use HTTPS in production
- Implement rate limiting for API endpoints
- Add authentication for production use
- Regularly backup the database
- Review and sanitize all user inputs

## License

Proprietary - All rights reserved
