<?php
/**
 * Swissturn Tool Management System
 * System Constants and Configuration
 *
 * This file contains all system-wide constants and configuration settings
 */

// Prevent direct access
if (!defined('SYSTEM_INIT')) {
    die('Direct access not permitted');
}

// =====================================================
// Application Settings
// =====================================================
define('APP_NAME', 'Swissturn Tool Management');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'Europe/Zurich');

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// =====================================================
// Path Configuration
// =====================================================
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('API_PATH', BASE_PATH . '/api');
define('AUTH_PATH', BASE_PATH . '/auth');
define('PAGES_PATH', BASE_PATH . '/pages');
define('COMPONENTS_PATH', BASE_PATH . '/components');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');

// =====================================================
// URL Configuration
// =====================================================
// Automatically detect base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . '://' . $host . rtrim($script, '/');

define('BASE_URL', $baseUrl);
define('ASSETS_URL', BASE_URL . '/assets');
define('API_URL', BASE_URL . '/api');

// =====================================================
// Session Configuration
// =====================================================
define('SESSION_NAME', 'SWISSTURN_TOOL_SESSION');
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('SESSION_COOKIE_SECURE', isset($_SERVER['HTTPS'])); // Secure only if HTTPS
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SAMESITE', 'Strict');

// =====================================================
// User Roles
// =====================================================
define('ROLE_ADMIN', 'admin');
define('ROLE_WORKER', 'worker');

// =====================================================
// Tool Status Constants
// =====================================================
define('TOOL_STATUS_ACTIVE', 'active');
define('TOOL_STATUS_NEAR_EXPIRY', 'near_expiry');
define('TOOL_STATUS_EXPIRED', 'expired');
define('TOOL_STATUS_NEEDS_REORDER', 'needs_reorder');

// =====================================================
// Tool Lifespan Types
// =====================================================
define('LIFESPAN_TYPE_TIME', 'time'); // Days-based
define('LIFESPAN_TYPE_USAGE', 'usage'); // Count-based

// =====================================================
// Transaction Types
// =====================================================
define('TRANSACTION_CHECKOUT', 'checkout');
define('TRANSACTION_CHECKIN', 'checkin');

// =====================================================
// Purchase Order Status
// =====================================================
define('ORDER_STATUS_PENDING', 'pending');
define('ORDER_STATUS_APPROVED', 'approved');
define('ORDER_STATUS_ORDERED', 'ordered');
define('ORDER_STATUS_RECEIVED', 'received');
define('ORDER_STATUS_CANCELLED', 'cancelled');

// =====================================================
// Purchase Order Priority
// =====================================================
define('ORDER_PRIORITY_LOW', 'low');
define('ORDER_PRIORITY_MEDIUM', 'medium');
define('ORDER_PRIORITY_HIGH', 'high');
define('ORDER_PRIORITY_URGENT', 'urgent');

// =====================================================
// Tool Lifecycle Thresholds
// =====================================================
define('NEAR_EXPIRY_THRESHOLD', 0.8); // 80% of lifespan used triggers near expiry

// =====================================================
// Pagination Settings
// =====================================================
define('ITEMS_PER_PAGE', 20);
define('ITEMS_PER_PAGE_REPORTS', 50);

// =====================================================
// Alert Messages
// =====================================================
define('MSG_LOGIN_SUCCESS', 'Welcome back! You have successfully logged in.');
define('MSG_LOGIN_FAILED', 'Invalid username or password.');
define('MSG_LOGOUT_SUCCESS', 'You have been logged out successfully.');
define('MSG_ACCESS_DENIED', 'You do not have permission to access this resource.');
define('MSG_SESSION_EXPIRED', 'Your session has expired. Please login again.');

define('MSG_TOOL_CHECKOUT_SUCCESS', 'Tool checked out successfully.');
define('MSG_TOOL_CHECKIN_SUCCESS', 'Tool checked in successfully.');
define('MSG_TOOL_EXPIRED', 'This tool has expired and cannot be checked out.');
define('MSG_TOOL_OUT_OF_STOCK', 'This tool is out of stock.');

define('MSG_TOOL_CREATED', 'Tool created successfully.');
define('MSG_TOOL_UPDATED', 'Tool updated successfully.');
define('MSG_TOOL_DELETED', 'Tool deleted successfully.');

define('MSG_USER_CREATED', 'User created successfully.');
define('MSG_USER_UPDATED', 'User updated successfully.');
define('MSG_USER_DELETED', 'User deleted successfully.');

define('MSG_SUPPLIER_CREATED', 'Supplier created successfully.');
define('MSG_SUPPLIER_UPDATED', 'Supplier updated successfully.');
define('MSG_SUPPLIER_DELETED', 'Supplier deleted successfully.');

define('MSG_ORDER_CREATED', 'Purchase order created successfully.');
define('MSG_ORDER_UPDATED', 'Purchase order updated successfully.');
define('MSG_ORDER_APPROVED', 'Purchase order approved successfully.');
define('MSG_ORDER_CANCELLED', 'Purchase order cancelled.');

define('MSG_ERROR_GENERIC', 'An error occurred. Please try again.');
define('MSG_ERROR_DATABASE', 'Database error occurred. Please contact administrator.');
define('MSG_ERROR_VALIDATION', 'Please check your input and try again.');

// =====================================================
// HTTP Response Codes
// =====================================================
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_INTERNAL_ERROR', 500);

// =====================================================
// Error Reporting
// =====================================================
// Set based on environment (change for production)
define('ENVIRONMENT', 'development'); // 'development' or 'production'

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/error_log');
}

// =====================================================
// Security Settings
// =====================================================
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 10);

// =====================================================
// Date and Time Formats
// =====================================================
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd M Y');
define('DISPLAY_DATETIME_FORMAT', 'd M Y H:i');

// =====================================================
// File Upload Settings (for future use)
// =====================================================
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_UPLOAD_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// =====================================================
// Chart Configuration (prevent infinite charts)
// =====================================================
define('CHART_MAX_DATA_POINTS', 30); // Maximum data points in charts
define('CHART_DEFAULT_DAYS', 30); // Default days to show in charts

// =====================================================
// API Rate Limiting (for future implementation)
// =====================================================
define('API_RATE_LIMIT', 100); // Requests per hour
define('API_RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds
