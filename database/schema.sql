-- ============================================
-- CREODENT CADCAM Work Management System
-- Database Schema
-- Version: 1.0
-- ============================================

-- Drop tables if exist (for clean install)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `case_audit_logs`;
DROP TABLE IF EXISTS `import_jobs`;
DROP TABLE IF EXISTS `cocr_orders`;
DROP TABLE IF EXISTS `print3d_orders`;
DROP TABLE IF EXISTS `solidex_orders`;
DROP TABLE IF EXISTS `case_items`;
DROP TABLE IF EXISTS `cases`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `departments`;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Core Tables
-- ============================================

-- Departments Table
CREATE TABLE `departments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'SOLIDEX, 3DPRINT, COCR, KOREA, QC',
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_code` (`code`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100),
  `department_id` INT UNSIGNED,
  `role` ENUM('super_admin', 'admin', 'manager', 'worker') NOT NULL DEFAULT 'worker',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_login_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`),
  INDEX `idx_department` (`department_id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cases Table (Main case information from Evolution Portal or manual entry)
CREATE TABLE `cases` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `external_case_no` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Case number from Evolution Portal',
  `source` ENUM('evolution_web_portal', 'google_sheets', 'manual') NOT NULL DEFAULT 'manual',
  `patient_name` VARCHAR(100),
  `lab_name` VARCHAR(100),
  `account_name` VARCHAR(100),
  `due_date` DATE,
  `location` ENUM('HV', 'NYC', 'NYCHV', 'HVNYC') DEFAULT 'HV',
  `status` ENUM('new', 'in_progress', 'done', 'on_hold', 'canceled', 'archived') NOT NULL DEFAULT 'new',
  `raw_payload` LONGTEXT COMMENT 'Original XML/JSON from Evolution Portal',
  `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'For optimistic locking',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_external_case_no` (`external_case_no`),
  INDEX `idx_status` (`status`),
  INDEX `idx_due_date` (`due_date`),
  INDEX `idx_location` (`location`),
  INDEX `idx_lab_name` (`lab_name`),
  INDEX `idx_created_at` (`created_at`),
  FULLTEXT INDEX `idx_fulltext_search` (`patient_name`, `lab_name`, `account_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case Items Table (Individual work items per department)
CREATE TABLE `case_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `work_type` VARCHAR(50) NOT NULL COMMENT 'SOLIDEX, 3DPRINT, COCR',
  `tooth_no` VARCHAR(20) COMMENT 'Tooth number (FDI notation)',
  `part_no` VARCHAR(50),
  `count` INT UNSIGNED DEFAULT 1,
  `case_type` VARCHAR(50) COMMENT 'Crown, Bridge, Implant, etc.',
  `color` VARCHAR(50),
  `instruction` TEXT,
  `preferences` TEXT,
  `status` ENUM('pending', 'assigned', 'working', 'done', 'remake', 'rejected') NOT NULL DEFAULT 'pending',
  `assigned_to` INT UNSIGNED COMMENT 'User ID',
  `assigned_at` TIMESTAMP NULL,
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'For optimistic locking',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_case_id` (`case_id`),
  INDEX `idx_department` (`department_id`),
  INDEX `idx_work_type` (`work_type`),
  INDEX `idx_status` (`status`),
  INDEX `idx_assigned_to` (`assigned_to`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Department-Specific Tables (JSON Storage)
-- ============================================

-- Solidex Orders (stores Google Sheets compatible JSON)
CREATE TABLE `solidex_orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED NOT NULL,
  `payload_json` JSON NOT NULL COMMENT 'Complete Solidex data structure',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_case_id` (`case_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3D Print Orders
CREATE TABLE `print3d_orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED NOT NULL,
  `payload_json` JSON NOT NULL COMMENT 'Complete 3D Print data structure',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_case_id` (`case_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CoCr Orders
CREATE TABLE `cocr_orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED NOT NULL,
  `payload_json` JSON NOT NULL COMMENT 'Complete CoCr/ZEST data structure',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_case_id` (`case_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Audit and Logging Tables
-- ============================================

-- Case Audit Logs (tracks all changes)
CREATE TABLE `case_audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED,
  `user_id` INT UNSIGNED,
  `action` VARCHAR(50) NOT NULL COMMENT 'create, update, assign, status_change, import, etc.',
  `description` TEXT,
  `before_json` JSON COMMENT 'State before change',
  `after_json` JSON COMMENT 'State after change',
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_case_id` (`case_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Import Jobs (tracks sync operations with Evolution Portal and Google Sheets)
CREATE TABLE `import_jobs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `job_type` ENUM('evo_case_list', 'evo_case_info', 'gsheet_solidex', 'gsheet_3dprint', 'gsheet_cocr') NOT NULL,
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ended_at` TIMESTAMP NULL,
  `status` ENUM('running', 'success', 'error', 'partial') NOT NULL DEFAULT 'running',
  `message` TEXT,
  `records_processed` INT UNSIGNED DEFAULT 0,
  `records_failed` INT UNSIGNED DEFAULT 0,
  `raw_request` TEXT,
  `raw_response` LONGTEXT,
  INDEX `idx_job_type` (`job_type`),
  INDEX `idx_status` (`status`),
  INDEX `idx_started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Initial Data
-- ============================================

-- Insert default departments
INSERT INTO `departments` (`code`, `name`, `description`) VALUES
('SOLIDEX', 'Solidex Department', 'General dental prosthetics'),
('3DPRINT', '3D Print Department', '3D printing operations'),
('COCR', 'CoCr/ZEST Department', 'Co-Cr alloy and ZEST work'),
('KOREA', 'Korea Department', 'Korea operations'),
('QC', 'Quality Control', 'Quality control and inspection');

-- Insert default super admin user
-- Password: admin123 (hashed with PASSWORD_DEFAULT)
INSERT INTO `users` (`username`, `password_hash`, `name`, `email`, `role`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@creodent.com', 'super_admin', 'active');

-- Note: Default password is 'admin123' - PLEASE CHANGE after first login!

-- ============================================
-- Stored Procedures and Functions
-- ============================================

-- Procedure to get dashboard statistics
DELIMITER $$

CREATE PROCEDURE `get_dashboard_stats`()
BEGIN
    SELECT
        (SELECT COUNT(*) FROM cases WHERE DATE(created_at) = CURDATE()) as today_new_cases,
        (SELECT COUNT(*) FROM cases WHERE status = 'in_progress') as in_progress_cases,
        (SELECT COUNT(*) FROM cases WHERE status = 'new') as pending_cases,
        (SELECT COUNT(*) FROM cases WHERE status = 'done' AND DATE(completed_at) = CURDATE()) as completed_today,
        (SELECT COUNT(*) FROM case_items WHERE status = 'assigned') as assigned_items,
        (SELECT COUNT(DISTINCT assigned_to) FROM case_items WHERE status IN ('assigned', 'working')) as active_workers;
END$$

-- Procedure to get department workload
CREATE PROCEDURE `get_department_workload`()
BEGIN
    SELECT
        d.code,
        d.name,
        COUNT(DISTINCT ci.case_id) as total_cases,
        SUM(CASE WHEN ci.status = 'pending' THEN 1 ELSE 0 END) as pending_items,
        SUM(CASE WHEN ci.status = 'working' THEN 1 ELSE 0 END) as working_items,
        SUM(CASE WHEN ci.status = 'done' THEN 1 ELSE 0 END) as done_items
    FROM departments d
    LEFT JOIN case_items ci ON d.id = ci.department_id
    WHERE d.is_active = 1
    GROUP BY d.id, d.code, d.name;
END$$

DELIMITER ;

-- ============================================
-- Views
-- ============================================

-- View for case overview with department status
CREATE VIEW `view_case_overview` AS
SELECT
    c.id,
    c.external_case_no,
    c.patient_name,
    c.lab_name,
    c.due_date,
    c.location,
    c.status,
    c.created_at,
    GROUP_CONCAT(DISTINCT d.code ORDER BY d.code) as departments,
    COUNT(ci.id) as total_items,
    SUM(CASE WHEN ci.status = 'done' THEN 1 ELSE 0 END) as completed_items,
    SUM(CASE WHEN ci.status IN ('assigned', 'working') THEN 1 ELSE 0 END) as active_items
FROM cases c
LEFT JOIN case_items ci ON c.id = ci.case_id
LEFT JOIN departments d ON ci.department_id = d.id
GROUP BY c.id;

-- ============================================
-- Indexes for Performance
-- ============================================

-- Composite indexes for common queries
CREATE INDEX `idx_cases_status_due` ON `cases`(`status`, `due_date`);
CREATE INDEX `idx_case_items_status_assigned` ON `case_items`(`status`, `assigned_to`);
CREATE INDEX `idx_case_items_case_dept` ON `case_items`(`case_id`, `department_id`);

-- ============================================
-- Table Comments
-- ============================================

ALTER TABLE `departments` COMMENT = 'Stores department information for work classification';
ALTER TABLE `users` COMMENT = 'System users with role-based access control';
ALTER TABLE `cases` COMMENT = 'Main case records from Evolution Portal or manual entry';
ALTER TABLE `case_items` COMMENT = 'Individual work items per department and tooth';
ALTER TABLE `solidex_orders` COMMENT = 'Solidex-specific data in JSON format for Google Sheets compatibility';
ALTER TABLE `print3d_orders` COMMENT = '3D Print-specific data in JSON format';
ALTER TABLE `cocr_orders` COMMENT = 'CoCr/ZEST-specific data in JSON format';
ALTER TABLE `case_audit_logs` COMMENT = 'Audit trail for all case modifications';
ALTER TABLE `import_jobs` COMMENT = 'Tracks synchronization jobs with external systems';

-- ============================================
-- End of Schema
-- ============================================
