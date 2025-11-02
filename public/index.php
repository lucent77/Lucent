<?php
/**
 * CREODENT CADCAM Work Management System
 * Main Entry Point
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('America/New_York');

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
$config = require __DIR__ . '/../config/config.php';

// Initialize database
use App\Core\Database;
use App\Core\Router;
use App\Core\Auth;

try {
    Database::getInstance($config['database']);
    Auth::getInstance($config);
} catch (\Exception $e) {
    error_log('Initialization error: ' . $e->getMessage());
    http_response_code(500);
    die('System initialization failed. Please contact administrator.');
}

// Create router
$router = new Router();

// ============================================
// Public Routes (No authentication required)
// ============================================

$router->get('/', function() {
    header('Location: /dashboard');
    exit;
});

$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->post('/logout', 'AuthController@logout');

// ============================================
// Authenticated Routes
// ============================================

// Dashboard
$router->get('/dashboard', 'DashboardController@index');

// Cases
$router->get('/cases', 'CaseController@index');
$router->get('/cases/create', 'CaseController@create');
$router->post('/cases', 'CaseController@store');
$router->get('/cases/search', 'CaseController@search');
$router->get('/cases/{id}', 'CaseController@show');
$router->post('/cases/{id}', 'CaseController@update');
$router->post('/cases/{id}/status', 'CaseController@updateStatus');
$router->post('/cases/{id}/delete', 'CaseController@delete');

// Case Items
$router->post('/items/assign', 'CaseController@assignItem');
$router->post('/items/status', 'CaseController@updateItemStatus');
$router->post('/items/bulk-assign', 'CaseController@bulkAssign');

// Admin Routes
$router->get('/admin', 'AdminController@index');
$router->get('/admin/users', 'AdminController@users');
$router->post('/admin/users', 'AdminController@createUser');
$router->post('/admin/users/{id}', 'AdminController@updateUser');
$router->post('/admin/users/{id}/delete', 'AdminController@deleteUser');

$router->get('/admin/departments', 'AdminController@departments');
$router->post('/admin/departments', 'AdminController@createDepartment');

$router->get('/admin/audit-logs', 'AdminController@auditLogs');

// Import Routes
$router->get('/admin/import', 'ImportController@index');
$router->post('/admin/import/test-connection', 'ImportController@testConnection');
$router->post('/admin/import/cases', 'ImportController@importCases');
$router->post('/admin/import/sync-recent', 'ImportController@syncRecent');

// User Profile
$router->get('/profile/change-password', 'AuthController@showChangePassword');
$router->post('/profile/change-password', 'AuthController@changePassword');

// ============================================
// API Routes
// ============================================

$router->get('/api/user', 'AuthController@me');
$router->get('/api/dashboard/stats', 'DashboardController@stats');
$router->get('/api/dashboard/my-work', 'DashboardController@myWork');

// ============================================
// Error Routes
// ============================================

$router->get('/error/403', function() {
    http_response_code(403);
    require __DIR__ . '/../views/errors/403.php';
});

$router->get('/error/404', function() {
    http_response_code(404);
    require __DIR__ . '/../views/errors/404.php';
});

$router->get('/error/500', function() {
    http_response_code(500);
    require __DIR__ . '/../views/errors/500.php';
});

// ============================================
// Dispatch Router
// ============================================

try {
    $router->dispatch();
} catch (\Exception $e) {
    error_log('Router error: ' . $e->getMessage());

    if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error',
            'error' => $config['app']['debug'] ? $e->getMessage() : null
        ]);
    } else {
        http_response_code(500);
        require __DIR__ . '/../views/errors/500.php';
    }
}
