<?php
/**
 * CREODENT Integrated Web Operations System
 * Configuration File
 *
 * SECURITY WARNING:
 * - Do NOT commit config.php to version control
 * - Copy this file to config.php and update with real credentials
 * - Ensure config.php has restricted file permissions (chmod 600)
 */

return [
    // ============================================================
    // APPLICATION SETTINGS
    // ============================================================
    'app' => [
        'name' => 'CREODENT Web Portal',
        'env' => 'production', // production, development, testing
        'debug' => false,
        'timezone' => 'America/New_York',
        'url' => 'https://your-domain.com',
    ],

    // ============================================================
    // DATABASE CONNECTION (Hostinger MySQL)
    // ============================================================
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'u359033001_CADCAM_WORK',
        'username' => 'u359033001_CADCAM_WORK',
        'password' => 'Creo$10001',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ],
    ],

    // ============================================================
    // EVOLUTION WEB PORTAL (V18) API SETTINGS
    // ============================================================
    'evolution' => [
        'enabled' => true,
        'base_url' => 'https://your-evolution-portal.com',
        'username' => 'your_evo_username',
        'password' => 'your_evo_password',
        'timeout' => 10, // seconds
        'max_retries' => 3,
        'retry_delay' => 2, // seconds between retries
    ],

    // ============================================================
    // GOOGLE SHEETS API (Optional - for legacy compatibility)
    // ============================================================
    'google_sheets' => [
        'enabled' => false,
        'credentials_path' => '/path/to/service-account.json',
        'spreadsheet_id' => 'your_spreadsheet_id',
        'sheets' => [
            'solidex' => 'Solidex Data',
            '3dprint' => '3D Print Data',
            'cocr' => 'CoCr Data',
        ],
    ],

    // ============================================================
    // SESSION SETTINGS
    // ============================================================
    'session' => [
        'name' => 'CREODENT_SESSION',
        'lifetime' => 7200, // 2 hours in seconds
        'secure' => true, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    // ============================================================
    // SECURITY SETTINGS
    // ============================================================
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'password_min_length' => 8,
        'session_regenerate_interval' => 300, // 5 minutes
        'max_login_attempts' => 5,
        'login_lockout_time' => 900, // 15 minutes
    ],

    // ============================================================
    // PAGINATION & LIMITS
    // ============================================================
    'pagination' => [
        'per_page' => 25,
        'max_per_page' => 100,
    ],

    // ============================================================
    // CHART & REPORTING SETTINGS
    // IMPORTANT: To prevent infinite charts, always enforce date ranges
    // ============================================================
    'charts' => [
        'default_days' => 30,
        'max_days' => 90,
        'max_data_points' => 90,
    ],

    // ============================================================
    // CRON/IMPORT SETTINGS
    // ============================================================
    'cron' => [
        'import_enabled' => true,
        'import_days_back' => 7, // How many days back to import on each run
        'import_batch_size' => 50, // Cases per batch
    ],

    // ============================================================
    // FILE UPLOAD SETTINGS
    // ============================================================
    'uploads' => [
        'max_size' => 10 * 1024 * 1024, // 10MB in bytes
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'stl'],
        'path' => __DIR__ . '/../storage/uploads',
    ],

    // ============================================================
    // LOGGING
    // ============================================================
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../storage/logs',
        'level' => 'info', // debug, info, warning, error
        'max_files' => 30, // Keep 30 days of logs
    ],

    // ============================================================
    // DEPARTMENT MAPPING (from VB.NET program)
    // Maps work types to department codes
    // ============================================================
    'department_mapping' => [
        'SOLIDEX' => 'SOLIDEX',
        'PRINT3D' => 'PRINT3D',
        'COCR' => 'COCR',
        'ZEST' => 'COCR', // Zest maps to CoCr department
    ],

    // ============================================================
    // FIELD MAPPINGS FOR JSON IMPORT (from Google Sheets/VB)
    // ============================================================
    'field_mappings' => [
        'solidex' => [
            'CASE #' => 'external_case_no',
            'DATE' => 'due_date',
            'LAB #' => 'lab_name',
            'PATIENT #' => 'patient_name',
            'LOCATION' => 'location',
            'TOOTH #' => 'tooth_no',
            'COUNT' => 'count',
            'INSTRUCTIONS' => 'instruction',
        ],
        '3dprint' => [
            'CASE #' => 'external_case_no',
            'DATE' => 'due_date',
            'LAB #' => 'lab_name',
            'PATIENT #' => 'patient_name',
            'TOOTH #' => 'tooth_no',
            'COUNT' => 'count',
            'INSTRUCTIONS' => 'instruction',
            'uploaded Korea' => 'preferences',
        ],
        'cocr' => [
            'CASE #' => 'external_case_no',
            'DATE' => 'due_date',
            'LAB #' => 'lab_name',
            'PATIENT #' => 'patient_name',
            'TOOTH #' => 'tooth_no',
            'COUNT' => 'count',
            'INSTRUCTIONS' => 'instruction',
            'TYPE' => 'preferences',
        ],
    ],
];
