-- CREODENT Integrated Web Operations System
-- MySQL Database Schema
-- Target: Hostinger MySQL (127.0.0.1:3306)
-- Database: u359033001_CADCAM_WORK
-- Generated: 2025-11-02

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- DEPARTMENTS TABLE
-- ============================================================
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL COMMENT 'SOLIDEX, PRINT3D, COCR, KOREA, QC',
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- USERS TABLE
-- ============================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(200) NOT NULL,
  `email` VARCHAR(255) NULL,
  `department_id` INT UNSIGNED NULL,
  `role` ENUM('super_admin','admin','manager','worker') NOT NULL DEFAULT 'worker',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_department` (`department_id`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CASES TABLE (Master case from Evolution or manual entry)
-- ============================================================
DROP TABLE IF EXISTS `cases`;
CREATE TABLE `cases` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_case_no` VARCHAR(100) NOT NULL COMMENT 'Evolution case number or VB/Google Sheets case number',
  `source` ENUM('evo','vb_program','google_sheet','manual') NOT NULL DEFAULT 'manual',
  `patient_name` VARCHAR(200) NULL,
  `lab_name` VARCHAR(200) NULL,
  `location` VARCHAR(50) NULL COMMENT 'HV, NYC, HVNYC, etc.',
  `due_date` DATE NULL,
  `status` ENUM('new','in_progress','done','on_hold','canceled') NOT NULL DEFAULT 'new',
  `raw_payload` LONGTEXT NULL COMMENT 'Raw XML/JSON from Evolution for traceability',
  `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Optimistic locking version',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_external_case_no` (`external_case_no`),
  KEY `idx_source` (`source`),
  KEY `idx_lab_name` (`lab_name`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CASE_ITEMS TABLE (Department-specific tasks per case)
-- ============================================================
DROP TABLE IF EXISTS `case_items`;
CREATE TABLE `case_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `work_type` ENUM('SOLIDEX','PRINT3D','COCR') NOT NULL,
  `tooth_no` VARCHAR(20) NULL,
  `count` INT UNSIGNED NOT NULL DEFAULT 1,
  `instruction` TEXT NULL,
  `preferences` TEXT NULL COMMENT 'Additional preferences/notes',
  `assigned_to` INT UNSIGNED NULL COMMENT 'Worker assigned to this item',
  `status` ENUM('pending','assigned','working','done','remake','rejected') NOT NULL DEFAULT 'pending',
  `assigned_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Optimistic locking version',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_department_id` (`department_id`),
  KEY `idx_work_type` (`work_type`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_case_items_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_case_items_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_case_items_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CASE_AUDIT_LOGS TABLE (Full audit trail)
-- ============================================================
DROP TABLE IF EXISTS `case_audit_logs`;
CREATE TABLE `case_audit_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NULL,
  `case_item_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'create, update, status_change, assign, import_evo, etc.',
  `description` TEXT NULL,
  `before_json` LONGTEXT NULL COMMENT 'State before change',
  `after_json` LONGTEXT NULL COMMENT 'State after change',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_case_item_id` (`case_item_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_audit_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_audit_case_item` FOREIGN KEY (`case_item_id`) REFERENCES `case_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- IMPORT_JOBS TABLE (Track all import operations)
-- ============================================================
DROP TABLE IF EXISTS `import_jobs`;
CREATE TABLE `import_jobs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_type` ENUM('evo_case_list','evo_case_info','vb_legacy','sheet_solidex','sheet_3dprint','sheet_cocr') NOT NULL,
  `started_at` DATETIME NOT NULL,
  `ended_at` DATETIME NULL,
  `status` ENUM('success','error','partial') NOT NULL DEFAULT 'success',
  `message` TEXT NULL,
  `records_processed` INT UNSIGNED NOT NULL DEFAULT 0,
  `records_failed` INT UNSIGNED NOT NULL DEFAULT 0,
  `raw_request` LONGTEXT NULL,
  `raw_response` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_job_type` (`job_type`),
  KEY `idx_started_at` (`started_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SOLIDEX_ORDERS TABLE (JSON mirror for backward compatibility)
-- ============================================================
DROP TABLE IF EXISTS `solidex_orders`;
CREATE TABLE `solidex_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NOT NULL,
  `payload_json` JSON NOT NULL COMMENT 'Full Solidex_data.json format',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  CONSTRAINT `fk_solidex_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PRINT3D_ORDERS TABLE (JSON mirror for backward compatibility)
-- ============================================================
DROP TABLE IF EXISTS `print3d_orders`;
CREATE TABLE `print3d_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NOT NULL,
  `payload_json` JSON NOT NULL COMMENT 'Full 3d_print_converted_data.json format',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  CONSTRAINT `fk_print3d_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- COCR_ORDERS TABLE (JSON mirror for backward compatibility)
-- ============================================================
DROP TABLE IF EXISTS `cocr_orders`;
CREATE TABLE `cocr_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NOT NULL,
  `payload_json` JSON NOT NULL COMMENT 'Full cocr_converted_data.json format',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  CONSTRAINT `fk_cocr_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SESSIONS TABLE (Optional: for database-backed sessions)
-- ============================================================
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` VARCHAR(128) NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF SCHEMA
-- ============================================================
