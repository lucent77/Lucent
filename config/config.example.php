<?php
/**
 * CREODENT CADCAM Work Management System
 * Configuration File Example
 *
 * Copy this file to config.php and update with your actual values
 * DO NOT commit config.php to version control
 */

return [
    // ============================================
    // Application Settings
    // ============================================
    'app' => [
        'name' => 'CREODENT CADCAM Work Manager',
        'version' => '1.0.0',
        'env' => 'production', // development, production
        'debug' => false, // Set to false in production
        'timezone' => 'America/New_York',
        'url' => 'https://your-domain.com',
    ],

    // ============================================
    // Database Configuration
    // ============================================
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'u359033001_CADCAM_WORK',
        'username' => 'u359033001_CADCAM_WORK',
        'password' => 'Creo$10001',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],

    // ============================================
    // Session Configuration
    // ============================================
    'session' => [
        'name' => 'CREODENT_SESSION',
        'lifetime' => 7200, // 2 hours in seconds
        'path' => '/',
        'domain' => '',
        'secure' => true, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ],

    // ============================================
    // Security Settings
    // ============================================
    'security' => [
        'app_key' => 'CHANGE_THIS_TO_RANDOM_32_CHAR_STRING', // Used for encryption
        'password_cost' => 10, // Bcrypt cost factor
        'csrf_token_name' => 'csrf_token',
        'csrf_token_lifetime' => 3600, // 1 hour
        'max_login_attempts' => 5,
        'login_lockout_time' => 900, // 15 minutes
    ],

    // ============================================
    // Evolution Web Portal Configuration
    // ============================================
    'evolution' => [
        'enabled' => true,
        'base_url' => 'https://your-evolution-portal-url.com',
        'username' => 'your_evolution_username',
        'password' => 'your_evolution_password',
        'timeout' => 30, // seconds
        'verify_ssl' => true,
        'retry_attempts' => 3,
        'retry_delay' => 2, // seconds
        'events' => [
            'account_login' => 'account_login',
            'cases_caselist' => 'cases_caselist',
            'case_caseinformation' => 'case_caseinformation',
            'case_noteget' => 'case_noteget',
            'case_noteadd' => 'case_noteadd',
            'case_imagelist' => 'case_imagelist',
        ],
    ],

    // ============================================
    // Google Sheets Configuration (Optional)
    // ============================================
    'google_sheets' => [
        'enabled' => false, // Enable for Google Sheets sync
        'service_account_file' => __DIR__ . '/../credentials/service-account.json',
        'spreadsheets' => [
            'solidex' => '1Zva5G5tW9_5zMlEzCiZbBkBS7q9F_sXtxYzhWnpqHN0',
            '3dprint' => '17qjwNZT14iUeppwkgh8RLXMpQs7GBi8AqbuO8M8tTvQ',
            'cocr' => '1lbQNL_7Kw-R3mFvvFteVVT6jmZ45S6c9Vw7htmJBkOM',
        ],
    ],

    // ============================================
    // Cron/Import Settings
    // ============================================
    'import' => [
        'auto_sync_enabled' => true,
        'sync_interval' => 3600, // 1 hour in seconds
        'sync_date_range' => 7, // days to look back
        'max_records_per_batch' => 100,
        'log_raw_data' => true, // Log raw XML/JSON for debugging
    ],

    // ============================================
    // Logging Configuration
    // ============================================
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../logs',
        'level' => 'info', // debug, info, warning, error
        'max_files' => 30, // Keep logs for 30 days
        'channels' => [
            'app' => 'app.log',
            'error' => 'error.log',
            'access' => 'access.log',
            'import' => 'import.log',
            'audit' => 'audit.log',
        ],
    ],

    // ============================================
    // Email Configuration (Optional)
    // ============================================
    'email' => [
        'enabled' => false,
        'driver' => 'smtp', // smtp, sendmail
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'tls', // tls, ssl
        'username' => 'your_email@example.com',
        'password' => 'your_email_password',
        'from_address' => 'noreply@creodent.com',
        'from_name' => 'CREODENT System',
    ],

    // ============================================
    // Pagination Settings
    // ============================================
    'pagination' => [
        'default_per_page' => 25,
        'max_per_page' => 100,
        'page_links_count' => 5,
    ],

    // ============================================
    // Upload Settings
    // ============================================
    'uploads' => [
        'path' => __DIR__ . '/../uploads',
        'max_size' => 10485760, // 10MB in bytes
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
    ],

    // ============================================
    // API Settings
    // ============================================
    'api' => [
        'enabled' => true,
        'rate_limit' => 100, // requests per minute
        'token_lifetime' => 86400, // 24 hours
        'require_authentication' => true,
    ],

    // ============================================
    // Department Mapping
    // ============================================
    'departments' => [
        'SOLIDEX' => [
            'name' => 'Solidex Department',
            'color' => '#3B82F6',
        ],
        '3DPRINT' => [
            'name' => '3D Print Department',
            'color' => '#10B981',
        ],
        'COCR' => [
            'name' => 'CoCr/ZEST Department',
            'color' => '#F59E0B',
        ],
        'KOREA' => [
            'name' => 'Korea Department',
            'color' => '#8B5CF6',
        ],
        'QC' => [
            'name' => 'Quality Control',
            'color' => '#EF4444',
        ],
    ],

    // ============================================
    // Status Definitions
    // ============================================
    'statuses' => [
        'case' => [
            'new' => ['label' => 'New', 'color' => 'blue'],
            'in_progress' => ['label' => 'In Progress', 'color' => 'yellow'],
            'done' => ['label' => 'Done', 'color' => 'green'],
            'on_hold' => ['label' => 'On Hold', 'color' => 'orange'],
            'canceled' => ['label' => 'Canceled', 'color' => 'red'],
            'archived' => ['label' => 'Archived', 'color' => 'gray'],
        ],
        'item' => [
            'pending' => ['label' => 'Pending', 'color' => 'gray'],
            'assigned' => ['label' => 'Assigned', 'color' => 'blue'],
            'working' => ['label' => 'Working', 'color' => 'yellow'],
            'done' => ['label' => 'Done', 'color' => 'green'],
            'remake' => ['label' => 'Remake', 'color' => 'orange'],
            'rejected' => ['label' => 'Rejected', 'color' => 'red'],
        ],
    ],

    // ============================================
    // Role Permissions
    // ============================================
    'roles' => [
        'super_admin' => [
            'label' => 'Super Administrator',
            'permissions' => ['*'], // All permissions
        ],
        'admin' => [
            'label' => 'Administrator',
            'permissions' => [
                'cases.view', 'cases.create', 'cases.edit', 'cases.delete',
                'users.view', 'users.create', 'users.edit',
                'departments.view', 'import.run', 'reports.view',
            ],
        ],
        'manager' => [
            'label' => 'Manager',
            'permissions' => [
                'cases.view', 'cases.create', 'cases.edit',
                'cases.assign', 'reports.view',
            ],
        ],
        'worker' => [
            'label' => 'Worker',
            'permissions' => [
                'cases.view', 'cases.update_status',
            ],
        ],
    ],
];
