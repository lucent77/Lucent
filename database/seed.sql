-- CREODENT Integrated Web Operations System
-- Seed Data for Initial Setup
-- Generated: 2025-11-02

SET NAMES utf8mb4;

-- ============================================================
-- DEPARTMENTS
-- ============================================================
INSERT INTO `departments` (`code`, `name`, `description`, `is_active`) VALUES
('SOLIDEX', 'Solidex Department', 'Solidex work processing', 1),
('PRINT3D', '3D Print Department', '3D printing work processing', 1),
('COCR', 'CoCr/Zest Department', 'CoCr and Zest work processing', 1),
('KOREA', 'Korea Department', 'Korea-related processing', 1),
('QC', 'Quality Control', 'Quality control and inspection', 1);

-- ============================================================
-- USERS (Default password for all: "password123")
-- Password hash generated using: password_hash('password123', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO `users` (`username`, `password_hash`, `full_name`, `email`, `department_id`, `role`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@creodent.com', NULL, 'super_admin', 'active'),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Department Manager', 'manager@creodent.com', 1, 'manager', 'active'),
('worker1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Worker One', 'worker1@creodent.com', 1, 'worker', 'active'),
('worker2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Worker Two', 'worker2@creodent.com', 2, 'worker', 'active'),
('qc1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'QC Inspector', 'qc@creodent.com', 5, 'worker', 'active');

-- ============================================================
-- SAMPLE CASE (for testing)
-- ============================================================
INSERT INTO `cases` (`external_case_no`, `source`, `patient_name`, `lab_name`, `location`, `due_date`, `status`, `version`) VALUES
('CASE-2025-001', 'manual', 'John Doe', 'Test Lab', 'HV', '2025-11-15', 'new', 1);

-- Get the case ID for reference
SET @case_id = LAST_INSERT_ID();

-- ============================================================
-- SAMPLE CASE ITEMS
-- ============================================================
INSERT INTO `case_items` (`case_id`, `department_id`, `work_type`, `tooth_no`, `count`, `instruction`, `status`, `version`) VALUES
(@case_id, 1, 'SOLIDEX', '11', 1, 'Standard crown', 'pending', 1),
(@case_id, 2, 'PRINT3D', '21,22', 2, 'Model base', 'pending', 1);

-- ============================================================
-- END OF SEED DATA
-- ============================================================

-- IMPORTANT NOTES:
-- 1. Default password for all users is "password123"
-- 2. Change the admin password immediately after first login
-- 3. Add real users as needed through the admin interface
