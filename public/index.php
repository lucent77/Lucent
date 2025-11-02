<?php
/**
 * CREODENT Integrated Web Operations System
 * Front Controller - Entry Point for all HTTP requests
 *
 * This file handles:
 * - Request routing
 * - Session management
 * - Error handling
 * - Security middleware
 */

declare(strict_types=1);

// Start output buffering
ob_start();

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('VIEWS_PATH', BASE_PATH . '/views');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Load configuration
$config = require CONFIG_PATH . '/config.php';

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Error reporting based on environment
if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', $config['session']['samesite']);

if ($config['session']['secure']) {
    ini_set('session.cookie_secure', '1');
}

session_name($config['session']['name']);
session_start();

// Autoloader (simple PSR-4-like autoloader)
spl_autoload_register(function ($class) {
    $prefix = '';
    $base_dir = APP_PATH . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Class doesn't use the namespace prefix, try to load from app directory
        $file = $base_dir . str_replace('\\', '/', $class) . '.php';
    } else {
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    }

    if (file_exists($file)) {
        require $file;
    }
});

// Make config globally available
$GLOBALS['config'] = $config;

// Helper function to get config
function config(string $key = null, $default = null) {
    if ($key === null) {
        return $GLOBALS['config'];
    }

    $keys = explode('.', $key);
    $value = $GLOBALS['config'];

    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }

    return $value;
}

// Helper function to redirect
function redirect(string $path, int $statusCode = 302): void {
    header('Location: ' . $path, true, $statusCode);
    exit;
}

// Helper function to check if user is logged in
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

// Helper function to get current user
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

// Helper function to check user role
function hasRole(string $role): bool {
    $user = currentUser();
    return $user && $user['role'] === $role;
}

// Helper function to check if user has any of the roles
function hasAnyRole(array $roles): bool {
    $user = currentUser();
    return $user && in_array($user['role'], $roles);
}

// Helper function to generate CSRF token
function csrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Helper function to verify CSRF token
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Helper function to escape output
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper function to return JSON response
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Get the requested URL
$url = $_GET['url'] ?? '';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = $url ?: 'dashboard';

// Parse the URL into parts
$urlParts = explode('/', $url);
$controller = $urlParts[0] ?? 'dashboard';
$action = $urlParts[1] ?? 'index';
$params = array_slice($urlParts, 2);

// Request method
$method = $_SERVER['REQUEST_METHOD'];

// Routes that don't require authentication
$publicRoutes = ['login', 'logout', 'api'];

// Check authentication
if (!in_array($controller, $publicRoutes) && !isLoggedIn()) {
    redirect('/login');
}

// Session regeneration for security
if (isLoggedIn()) {
    $lastRegeneration = $_SESSION['last_regeneration'] ?? 0;
    if (time() - $lastRegeneration > config('security.session_regenerate_interval', 300)) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// CSRF protection for POST requests (except login)
if ($method === 'POST' && !in_array($controller, ['login', 'api'])) {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrfToken($token)) {
        if (str_starts_with($url, 'api/')) {
            jsonResponse(['success' => false, 'error' => 'CSRF token validation failed'], 403);
        } else {
            http_response_code(403);
            die('CSRF token validation failed. Please refresh the page and try again.');
        }
    }
}

// Route the request
try {
    // Map URL segments to controller classes
    $controllerMap = [
        'login' => 'Controllers/AuthController',
        'logout' => 'Controllers/AuthController',
        'dashboard' => 'Controllers/DashboardController',
        'cases' => 'Controllers/CaseController',
        'admin' => 'Controllers/AdminController',
        'api' => 'Controllers/ApiController',
    ];

    $controllerFile = APP_PATH . '/' . ($controllerMap[$controller] ?? 'Controllers/DashboardController') . '.php';

    if (!file_exists($controllerFile)) {
        throw new Exception('Controller not found: ' . $controller);
    }

    require_once $controllerFile;

    // Get controller class name
    $controllerClassName = basename($controllerFile, '.php');

    // Instantiate controller
    $controllerInstance = new $controllerClassName();

    // Determine which method to call
    if ($controller === 'login') {
        $controllerInstance->index();
    } elseif ($controller === 'logout') {
        $controllerInstance->logout();
    } elseif (method_exists($controllerInstance, $action)) {
        call_user_func_array([$controllerInstance, $action], $params);
    } else {
        throw new Exception('Action not found: ' . $action);
    }

} catch (Exception $e) {
    // Error handling
    if (config('app.debug')) {
        echo '<h1>Error</h1>';
        echo '<p>' . e($e->getMessage()) . '</p>';
        echo '<pre>' . e($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        require VIEWS_PATH . '/errors/500.php';
    }
}

// Flush output buffer
ob_end_flush();
