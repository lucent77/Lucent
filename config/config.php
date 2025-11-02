<?php
/**
 * CREODENT CADCAM Work Management System
 * Configuration File
 */

return [
    'app' => [
        'name' => 'CREODENT CADCAM Work Manager',
        'version' => '1.0.0',
        'env' => 'production',
        'debug' => false,
        'timezone' => 'America/New_York',
        'url' => 'https://your-domain.com',
    ],

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

    'session' => [
        'name' => 'CREODENT_SESSION',
        'lifetime' => 7200,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ],

    'security' => [
        'app_key' => bin2hex(random_bytes(16)),
        'password_cost' => 10,
        'csrf_token_name' => 'csrf_token',
        'csrf_token_lifetime' => 3600,
        'max_login_attempts' => 5,
        'login_lockout_time' => 900,
    ],

    'evolution' => [
        'enabled' => false, // Set to true when configured
        'base_url' => 'https://your-evolution-portal-url.com',
        'username' => 'your_evolution_username',
        'password' => 'your_evolution_password',
        'timeout' => 30,
        'verify_ssl' => true,
        'retry_attempts' => 3,
        'retry_delay' => 2,
        'events' => [
            'account_login' => 'account_login',
            'cases_caselist' => 'cases_caselist',
            'case_caseinformation' => 'case_caseinformation',
            'case_noteget' => 'case_noteget',
            'case_noteadd' => 'case_noteadd',
            'case_imagelist' => 'case_imagelist',
        ],
    ],

    'google_sheets' => [
        'enabled' => false,
        'service_account_file' => __DIR__ . '/../credentials/service-account.json',
        'spreadsheets' => [
            'solidex' => '1Zva5G5tW9_5zMlEzCiZbBkBS7q9F_sXtxYzhWnpqHN0',
            '3dprint' => '17qjwNZT14iUeppwkgh8RLXMpQs7GBi8AqbuO8M8tTvQ',
            'cocr' => '1lbQNL_7Kw-R3mFvvFteVVT6jmZ45S6c9Vw7htmJBkOM',
        ],
    ],

    'import' => [
        'auto_sync_enabled' => false,
        'sync_interval' => 3600,
        'sync_date_range' => 7,
        'max_records_per_batch' => 100,
        'log_raw_data' => true,
    ],

    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../logs',
        'level' => 'info',
        'max_files' => 30,
        'channels' => [
            'app' => 'app.log',
            'error' => 'error.log',
            'access' => 'access.log',
            'import' => 'import.log',
            'audit' => 'audit.log',
        ],
    ],

    'email' => [
        'enabled' => false,
        'driver' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'your_email@example.com',
        'password' => 'your_email_password',
        'from_address' => 'noreply@creodent.com',
        'from_name' => 'CREODENT System',
    ],

    'pagination' => [
        'default_per_page' => 25,
        'max_per_page' => 100,
        'page_links_count' => 5,
    ],

    'uploads' => [
        'path' => __DIR__ . '/../uploads',
        'max_size' => 10485760,
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
    ],

    'api' => [
        'enabled' => true,
        'rate_limit' => 100,
        'token_lifetime' => 86400,
        'require_authentication' => true,
    ],

    'departments' => [
        'SOLIDEX' => ['name' => 'Solidex Department', 'color' => '#3B82F6'],
        '3DPRINT' => ['name' => '3D Print Department', 'color' => '#10B981'],
        'COCR' => ['name' => 'CoCr/ZEST Department', 'color' => '#F59E0B'],
        'KOREA' => ['name' => 'Korea Department', 'color' => '#8B5CF6'],
        'QC' => ['name' => 'Quality Control', 'color' => '#EF4444'],
    ],

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

    'roles' => [
        'super_admin' => [
            'label' => 'Super Administrator',
            'permissions' => ['*'],
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
