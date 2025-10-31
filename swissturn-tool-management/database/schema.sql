-- Swissturn Tool Management System
-- Database Schema
-- Created: 2025-10-31

-- Drop tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS purchase_orders;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS tools;
DROP TABLE IF EXISTS tool_categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS users;

-- =====================================================
-- Table: users
-- Description: System users (administrators and workers)
-- =====================================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'worker') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: suppliers
-- Description: Tool suppliers information
-- =====================================================
CREATE TABLE suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_name (supplier_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: tool_categories
-- Description: Tool categorization
-- =====================================================
CREATE TABLE tool_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: tools
-- Description: Master tool inventory
-- =====================================================
CREATE TABLE tools (
    tool_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    tool_name VARCHAR(100) NOT NULL,
    tool_description TEXT,
    tool_size VARCHAR(50),
    supplier_id INT,
    supplier_model_number VARCHAR(100),
    current_stock INT DEFAULT 0,
    minimum_stock INT DEFAULT 5,
    usage_count INT DEFAULT 0,
    first_use_date DATE,
    lifespan_type ENUM('time', 'usage') NOT NULL,
    lifespan_limit INT NOT NULL COMMENT 'Days for time-based, count for usage-based',
    status ENUM('active', 'near_expiry', 'expired', 'needs_reorder') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES tool_categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
    INDEX idx_tool_name (tool_name),
    INDEX idx_status (status),
    INDEX idx_category (category_id),
    INDEX idx_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: transactions
-- Description: Tool check-out and check-in records
-- =====================================================
CREATE TABLE transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    user_id INT NOT NULL,
    transaction_type ENUM('checkout', 'checkin') NOT NULL,
    job_id VARCHAR(50),
    task_description TEXT,
    quantity INT DEFAULT 1,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (tool_id) REFERENCES tools(tool_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_tool_id (tool_id),
    INDEX idx_user_id (user_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_job_id (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: purchase_orders
-- Description: Purchase order management
-- =====================================================
CREATE TABLE purchase_orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    supplier_id INT NOT NULL,
    quantity INT NOT NULL,
    status ENUM('pending', 'approved', 'ordered', 'received', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    requested_by INT,
    requested_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT,
    approved_date TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (tool_id) REFERENCES tools(tool_id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_requested_date (requested_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: audit_logs
-- Description: System audit trail
-- =====================================================
CREATE TABLE audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Initial System Configuration
-- =====================================================

-- Insert default admin user
-- Password: admin123 (hashed with bcrypt)
INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@swissturn.com', 'admin', TRUE);

-- Insert default tool categories
INSERT INTO tool_categories (category_name, description) VALUES
('Cutting Tools', 'Tools for cutting and machining operations'),
('Measuring Tools', 'Precision measurement instruments'),
('Hand Tools', 'Manual operation tools'),
('Power Tools', 'Electric and pneumatic tools'),
('Safety Equipment', 'Personal protective equipment'),
('Fixtures', 'Work holding devices');

-- Schema creation completed successfully
