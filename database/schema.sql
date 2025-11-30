-- ============================================
-- Voice Prescription System Database Schema
-- MySQL 5.7+ / MariaDB 10.2+
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS voice_prescription_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE voice_prescription_db;

-- ============================================
-- Table: doctors
-- Stores doctor information and credentials
-- ============================================
CREATE TABLE IF NOT EXISTS doctors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(100) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    license_number VARCHAR(50) NULL,
    department VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_doctor_code (doctor_code),
    INDEX idx_is_active (is_active),
    INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: cases
-- Main table for patient cases/consultations
-- ============================================
CREATE TABLE IF NOT EXISTS cases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(50) NOT NULL UNIQUE,
    doctor_id INT UNSIGNED NULL,
    patient_name VARCHAR(100) NULL,
    patient_dob DATE NULL,
    patient_gender ENUM('M', 'F', 'O') NULL,
    patient_phone VARCHAR(20) NULL,
    patient_id_number VARCHAR(50) NULL,
    chief_complaint TEXT NULL,
    symptoms TEXT NULL,
    diagnosis TEXT NULL,
    treatment_plan TEXT NULL,
    medications JSON NULL,
    allergies TEXT NULL,
    vital_signs JSON NULL,
    notes TEXT NULL,
    status ENUM('recording', 'transcribing', 'analyzing', 'followup', 'pending_confirmation', 'confirmed', 'completed', 'cancelled') DEFAULT 'recording',
    ai_structured_data JSON NULL,
    missing_fields JSON NULL,
    followup_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,

    INDEX idx_case_number (case_number),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_patient_name (patient_name),

    CONSTRAINT fk_cases_doctor FOREIGN KEY (doctor_id)
        REFERENCES doctors(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: case_audio
-- Stores audio recordings for each case
-- ============================================
CREATE TABLE IF NOT EXISTS case_audio (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id INT UNSIGNED NOT NULL,
    audio_type ENUM('initial', 'followup', 'clarification', 'final') DEFAULT 'initial',
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT UNSIGNED NULL,
    mime_type VARCHAR(100) NULL,
    duration_seconds DECIMAL(10, 2) NULL,
    sample_rate INT NULL,
    transcript TEXT NULL,
    transcript_confidence DECIMAL(5, 4) NULL,
    language_code VARCHAR(10) DEFAULT 'ko-KR',
    processing_status ENUM('uploaded', 'processing', 'transcribed', 'failed') DEFAULT 'uploaded',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,

    INDEX idx_case_id (case_id),
    INDEX idx_audio_type (audio_type),
    INDEX idx_processing_status (processing_status),
    INDEX idx_created_at (created_at),

    CONSTRAINT fk_case_audio_case FOREIGN KEY (case_id)
        REFERENCES cases(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: prescription_fields
-- Stores structured prescription field data
-- ============================================
CREATE TABLE IF NOT EXISTS prescription_fields (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id INT UNSIGNED NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_value TEXT NULL,
    field_type ENUM('text', 'number', 'date', 'boolean', 'array', 'object') DEFAULT 'text',
    is_required TINYINT(1) DEFAULT 0,
    is_filled TINYINT(1) DEFAULT 0,
    confidence_score DECIMAL(5, 4) NULL,
    source ENUM('voice', 'ai_extracted', 'ai_inferred', 'manual', 'followup') DEFAULT 'voice',
    verified_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_case_id (case_id),
    INDEX idx_field_name (field_name),
    INDEX idx_is_required (is_required),
    INDEX idx_is_filled (is_filled),

    CONSTRAINT fk_prescription_fields_case FOREIGN KEY (case_id)
        REFERENCES cases(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_prescription_fields_verified FOREIGN KEY (verified_by)
        REFERENCES doctors(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: ai_logs
-- Logs all AI API interactions for debugging/audit
-- ============================================
CREATE TABLE IF NOT EXISTS ai_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id INT UNSIGNED NULL,
    service_type ENUM('speech_to_text', 'gemini_analyze', 'gemini_followup', 'text_to_speech', 'gemini_summary') NOT NULL,
    request_payload JSON NULL,
    response_payload JSON NULL,
    prompt_template VARCHAR(100) NULL,
    tokens_used INT NULL,
    processing_time_ms INT NULL,
    status ENUM('success', 'error', 'timeout', 'rate_limited') DEFAULT 'success',
    error_message TEXT NULL,
    api_endpoint VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_case_id (case_id),
    INDEX idx_service_type (service_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),

    CONSTRAINT fk_ai_logs_case FOREIGN KEY (case_id)
        REFERENCES cases(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: followup_questions
-- Stores AI-generated follow-up questions
-- ============================================
CREATE TABLE IF NOT EXISTS followup_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id INT UNSIGNED NOT NULL,
    question_number INT DEFAULT 1,
    question_text TEXT NOT NULL,
    question_context TEXT NULL,
    target_field VARCHAR(100) NULL,
    tts_audio_path VARCHAR(500) NULL,
    tts_audio_generated TINYINT(1) DEFAULT 0,
    answer_text TEXT NULL,
    answer_audio_id INT UNSIGNED NULL,
    is_answered TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    answered_at TIMESTAMP NULL,

    INDEX idx_case_id (case_id),
    INDEX idx_is_answered (is_answered),
    INDEX idx_question_number (question_number),

    CONSTRAINT fk_followup_case FOREIGN KEY (case_id)
        REFERENCES cases(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_followup_answer_audio FOREIGN KEY (answer_audio_id)
        REFERENCES case_audio(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: frontdesk_queue
-- Queue for front desk to process prescriptions
-- ============================================
CREATE TABLE IF NOT EXISTS frontdesk_queue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id INT UNSIGNED NOT NULL,
    queue_number INT NULL,
    priority ENUM('urgent', 'high', 'normal', 'low') DEFAULT 'normal',
    status ENUM('waiting', 'processing', 'ready', 'dispensed', 'cancelled') DEFAULT 'waiting',
    assigned_to VARCHAR(100) NULL,
    prescription_summary TEXT NULL,
    medications_count INT DEFAULT 0,
    estimated_wait_minutes INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    dispensed_at TIMESTAMP NULL,

    INDEX idx_case_id (case_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_queue_number (queue_number),
    INDEX idx_created_at (created_at),

    CONSTRAINT fk_queue_case FOREIGN KEY (case_id)
        REFERENCES cases(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: prescriptions (Final Prescription Record)
-- Stores finalized prescription documents
-- ============================================
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id INT UNSIGNED NOT NULL,
    prescription_number VARCHAR(50) NOT NULL UNIQUE,
    doctor_id INT UNSIGNED NULL,
    patient_name VARCHAR(100) NOT NULL,
    patient_dob DATE NULL,
    patient_gender ENUM('M', 'F', 'O') NULL,
    diagnosis TEXT NULL,
    medications JSON NOT NULL,
    instructions TEXT NULL,
    warnings TEXT NULL,
    follow_up_date DATE NULL,
    is_signed TINYINT(1) DEFAULT 0,
    signed_at TIMESTAMP NULL,
    pdf_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_case_id (case_id),
    INDEX idx_prescription_number (prescription_number),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_created_at (created_at),

    CONSTRAINT fk_prescription_case FOREIGN KEY (case_id)
        REFERENCES cases(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_prescription_doctor FOREIGN KEY (doctor_id)
        REFERENCES doctors(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Sample Data
-- ============================================

-- Sample doctors
INSERT INTO doctors (doctor_code, name, specialty, email, department) VALUES
('DR001', 'Dr. Kim Min-jun', 'Internal Medicine', 'kim.minjun@hospital.com', 'Internal Medicine'),
('DR002', 'Dr. Park Soo-yeon', 'Family Medicine', 'park.sooyeon@hospital.com', 'Family Medicine'),
('DR003', 'Dr. Lee Jae-won', 'Pediatrics', 'lee.jaewon@hospital.com', 'Pediatrics');

-- ============================================
-- Views for Reporting
-- ============================================

-- Active cases view
CREATE OR REPLACE VIEW v_active_cases AS
SELECT
    c.id,
    c.case_number,
    c.patient_name,
    c.status,
    c.created_at,
    d.name as doctor_name,
    d.department,
    (SELECT COUNT(*) FROM case_audio ca WHERE ca.case_id = c.id) as audio_count,
    (SELECT COUNT(*) FROM followup_questions fq WHERE fq.case_id = c.id AND fq.is_answered = 0) as pending_questions
FROM cases c
LEFT JOIN doctors d ON c.doctor_id = d.id
WHERE c.status NOT IN ('completed', 'cancelled')
ORDER BY c.created_at DESC;

-- Front desk queue view
CREATE OR REPLACE VIEW v_frontdesk_dashboard AS
SELECT
    fq.id as queue_id,
    fq.queue_number,
    fq.priority,
    fq.status as queue_status,
    c.case_number,
    c.patient_name,
    c.diagnosis,
    c.medications,
    d.name as doctor_name,
    fq.created_at,
    fq.estimated_wait_minutes,
    TIMESTAMPDIFF(MINUTE, fq.created_at, NOW()) as actual_wait_minutes
FROM frontdesk_queue fq
JOIN cases c ON fq.case_id = c.id
LEFT JOIN doctors d ON c.doctor_id = d.id
WHERE fq.status IN ('waiting', 'processing', 'ready')
ORDER BY
    FIELD(fq.priority, 'urgent', 'high', 'normal', 'low'),
    fq.created_at ASC;

-- Daily statistics view
CREATE OR REPLACE VIEW v_daily_stats AS
SELECT
    DATE(created_at) as date,
    COUNT(*) as total_cases,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_cases,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_cases,
    AVG(followup_count) as avg_followups
FROM cases
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- ============================================
-- Stored Procedures
-- ============================================

DELIMITER //

-- Generate unique case number
CREATE PROCEDURE sp_generate_case_number(OUT new_case_number VARCHAR(50))
BEGIN
    DECLARE today_count INT;
    DECLARE date_prefix VARCHAR(10);

    SET date_prefix = DATE_FORMAT(NOW(), '%Y%m%d');

    SELECT COUNT(*) + 1 INTO today_count
    FROM cases
    WHERE DATE(created_at) = CURDATE();

    SET new_case_number = CONCAT('CASE-', date_prefix, '-', LPAD(today_count, 4, '0'));
END //

-- Generate queue number
CREATE PROCEDURE sp_generate_queue_number(OUT new_queue_number INT)
BEGIN
    SELECT COALESCE(MAX(queue_number), 0) + 1 INTO new_queue_number
    FROM frontdesk_queue
    WHERE DATE(created_at) = CURDATE();
END //

-- Update case status with timestamp
CREATE PROCEDURE sp_update_case_status(
    IN p_case_id INT,
    IN p_new_status VARCHAR(50)
)
BEGIN
    UPDATE cases
    SET
        status = p_new_status,
        confirmed_at = CASE WHEN p_new_status = 'confirmed' THEN NOW() ELSE confirmed_at END,
        completed_at = CASE WHEN p_new_status = 'completed' THEN NOW() ELSE completed_at END
    WHERE id = p_case_id;
END //

DELIMITER ;

-- ============================================
-- Triggers
-- ============================================

DELIMITER //

-- Auto-generate queue number
CREATE TRIGGER trg_frontdesk_queue_number
BEFORE INSERT ON frontdesk_queue
FOR EACH ROW
BEGIN
    IF NEW.queue_number IS NULL THEN
        SELECT COALESCE(MAX(queue_number), 0) + 1 INTO @new_num
        FROM frontdesk_queue
        WHERE DATE(created_at) = CURDATE();
        SET NEW.queue_number = @new_num;
    END IF;
END //

DELIMITER ;

-- ============================================
-- Grant Permissions (adjust user as needed)
-- ============================================
-- GRANT SELECT, INSERT, UPDATE, DELETE ON voice_prescription_db.* TO 'your_app_user'@'localhost';
-- FLUSH PRIVILEGES;
