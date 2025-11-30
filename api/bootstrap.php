<?php
/**
 * API Bootstrap
 * Common initialization for all API endpoints
 */

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Define root path
define('APP_ROOT', dirname(__DIR__));

// Set timezone
date_default_timezone_set('Asia/Seoul');

// Load configuration
require_once APP_ROOT . '/config/env.php';
require_once APP_ROOT . '/config/database.php';

// Load services
require_once APP_ROOT . '/services/GoogleAIService.php';
require_once APP_ROOT . '/services/AudioService.php';
require_once APP_ROOT . '/services/PrescriptionService.php';

// Load models
require_once APP_ROOT . '/models/CaseModel.php';
require_once APP_ROOT . '/models/PrescriptionModel.php';

// Set headers for JSON API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Send JSON response
 *
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send error response
 *
 * @param string $message Error message
 * @param int $statusCode HTTP status code
 * @param array $details Additional error details
 */
function errorResponse(string $message, int $statusCode = 400, array $details = []): void
{
    $response = [
        'success' => false,
        'error' => $message,
        'timestamp' => date('c')
    ];

    if (!empty($details)) {
        $response['details'] = $details;
    }

    jsonResponse($response, $statusCode);
}

/**
 * Send success response
 *
 * @param array $data Response data
 * @param string|null $message Success message
 */
function successResponse(array $data = [], ?string $message = null): void
{
    $response = [
        'success' => true,
        'timestamp' => date('c')
    ];

    if ($message) {
        $response['message'] = $message;
    }

    jsonResponse(array_merge($response, $data));
}

/**
 * Get JSON request body
 *
 * @return array Request data
 */
function getJsonBody(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }

    return $data ?? [];
}

/**
 * Validate required fields
 *
 * @param array $data Data to validate
 * @param array $requiredFields Required field names
 * @return array Missing fields
 */
function validateRequired(array $data, array $requiredFields): array
{
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }

    return $missing;
}

/**
 * Get request parameter (GET or POST)
 *
 * @param string $name Parameter name
 * @param mixed $default Default value
 * @return mixed Parameter value
 */
function getParam(string $name, $default = null)
{
    return $_REQUEST[$name] ?? $default;
}

/**
 * Require specific HTTP method
 *
 * @param string|array $methods Allowed method(s)
 */
function requireMethod($methods): void
{
    $methods = is_array($methods) ? $methods : [$methods];

    if (!in_array($_SERVER['REQUEST_METHOD'], $methods)) {
        errorResponse('Method not allowed', 405);
    }
}

/**
 * Log API request for debugging
 *
 * @param string $endpoint Endpoint name
 * @param array $data Request data
 */
function logRequest(string $endpoint, array $data = []): void
{
    if (APP_DEBUG) {
        error_log(sprintf(
            "[API] %s %s - %s",
            $_SERVER['REQUEST_METHOD'],
            $endpoint,
            json_encode($data)
        ));
    }
}

// Global exception handler
set_exception_handler(function (Throwable $e) {
    error_log("API Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

    $message = APP_DEBUG ? $e->getMessage() : 'An internal error occurred';
    errorResponse($message, 500);
});

// Global error handler
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
