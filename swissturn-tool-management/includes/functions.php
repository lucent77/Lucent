<?php
/**
 * Swissturn Tool Management System
 * Utility Functions
 *
 * This file contains common utility functions used throughout the application
 */

/**
 * Sanitize input data
 *
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Hash password securely
 *
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
}

/**
 * Verify password against hash
 *
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Calculate tool status based on lifespan
 *
 * @param array $tool Tool data array
 * @return string Tool status
 */
function calculateToolStatus($tool) {
    // Check stock level first
    if ($tool['current_stock'] <= $tool['minimum_stock']) {
        return TOOL_STATUS_NEEDS_REORDER;
    }

    // If no first use date, tool is active
    if (empty($tool['first_use_date'])) {
        return TOOL_STATUS_ACTIVE;
    }

    $lifespan_type = $tool['lifespan_type'];
    $lifespan_limit = intval($tool['lifespan_limit']);

    if ($lifespan_type === LIFESPAN_TYPE_TIME) {
        // Time-based lifespan
        $first_use = new DateTime($tool['first_use_date']);
        $today = new DateTime();
        $days_used = $today->diff($first_use)->days;

        if ($days_used >= $lifespan_limit) {
            return TOOL_STATUS_EXPIRED;
        } elseif ($days_used >= ($lifespan_limit * NEAR_EXPIRY_THRESHOLD)) {
            return TOOL_STATUS_NEAR_EXPIRY;
        }

    } elseif ($lifespan_type === LIFESPAN_TYPE_USAGE) {
        // Usage-based lifespan
        $usage_count = intval($tool['usage_count']);

        if ($usage_count >= $lifespan_limit) {
            return TOOL_STATUS_EXPIRED;
        } elseif ($usage_count >= ($lifespan_limit * NEAR_EXPIRY_THRESHOLD)) {
            return TOOL_STATUS_NEAR_EXPIRY;
        }
    }

    return TOOL_STATUS_ACTIVE;
}

/**
 * Get tool lifecycle percentage
 *
 * @param array $tool Tool data array
 * @return float Percentage of lifecycle used (0-100)
 */
function getToolLifecyclePercentage($tool) {
    if (empty($tool['first_use_date'])) {
        return 0;
    }

    $lifespan_type = $tool['lifespan_type'];
    $lifespan_limit = intval($tool['lifespan_limit']);

    if ($lifespan_type === LIFESPAN_TYPE_TIME) {
        $first_use = new DateTime($tool['first_use_date']);
        $today = new DateTime();
        $days_used = $today->diff($first_use)->days;
        return min(100, ($days_used / $lifespan_limit) * 100);

    } elseif ($lifespan_type === LIFESPAN_TYPE_USAGE) {
        $usage_count = intval($tool['usage_count']);
        return min(100, ($usage_count / $lifespan_limit) * 100);
    }

    return 0;
}

/**
 * Get status badge HTML
 *
 * @param string $status Tool status
 * @return string HTML for status badge
 */
function getStatusBadge($status) {
    $badges = [
        TOOL_STATUS_ACTIVE => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>',
        TOOL_STATUS_NEAR_EXPIRY => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Near Expiry</span>',
        TOOL_STATUS_EXPIRED => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Expired</span>',
        TOOL_STATUS_NEEDS_REORDER => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Needs Reorder</span>'
    ];

    return $badges[$status] ?? '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Unknown</span>';
}

/**
 * Get priority badge HTML
 *
 * @param string $priority Order priority
 * @return string HTML for priority badge
 */
function getPriorityBadge($priority) {
    $badges = [
        ORDER_PRIORITY_LOW => '<span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">Low</span>',
        ORDER_PRIORITY_MEDIUM => '<span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">Medium</span>',
        ORDER_PRIORITY_HIGH => '<span class="px-2 py-1 text-xs font-semibold rounded bg-orange-100 text-orange-800">High</span>',
        ORDER_PRIORITY_URGENT => '<span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">Urgent</span>'
    ];

    return $badges[$priority] ?? '<span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">Unknown</span>';
}

/**
 * Format date for display
 *
 * @param string $date Date string
 * @param string $format Display format
 * @return string Formatted date
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date)) {
        return 'N/A';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Format datetime for display
 *
 * @param string $datetime Datetime string
 * @return string Formatted datetime
 */
function formatDateTime($datetime) {
    return formatDate($datetime, DISPLAY_DATETIME_FORMAT);
}

/**
 * Log audit trail
 *
 * @param int $user_id User ID
 * @param string $action Action performed
 * @param string $table_name Table affected
 * @param int $record_id Record ID
 * @param mixed $old_values Old values (optional)
 * @param mixed $new_values New values (optional)
 */
function logAudit($user_id, $action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

        $sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address)
                VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address)";

        $params = [
            ':user_id' => $user_id,
            ':action' => $action,
            ':table_name' => $table_name,
            ':record_id' => $record_id,
            ':old_values' => $old_values ? json_encode($old_values) : null,
            ':new_values' => $new_values ? json_encode($new_values) : null,
            ':ip_address' => $ip_address
        ];

        dbExecute($sql, $params);
    } catch (Exception $e) {
        error_log("Audit Log Error: " . $e->getMessage());
    }
}

/**
 * Send JSON response
 *
 * @param bool $success Success status
 * @param mixed $data Response data
 * @param string $message Response message
 * @param int $code HTTP status code
 */
function jsonResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date(DATETIME_FORMAT)
    ]);
    exit;
}

/**
 * Check if user is logged in
 *
 * @return bool True if logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 *
 * @param string $role Role to check
 * @return bool True if user has role
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require login
 * Redirects to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Require admin role
 * Shows error if user is not admin
 */
function requireAdmin() {
    requireLogin();
    if (!hasRole(ROLE_ADMIN)) {
        http_response_code(403);
        die('Access Denied: Administrator privileges required.');
    }
}

/**
 * Get current user ID
 *
 * @return int|null User ID or null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 *
 * @return string|null User role or null
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Generate CSRF token
 *
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 *
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escape output for HTML
 *
 * @param string $string String to escape
 * @return string Escaped string
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if tool needs automatic reorder
 *
 * @param array $tool Tool data
 * @return bool True if reorder needed
 */
function needsAutoReorder($tool) {
    return $tool['current_stock'] <= $tool['minimum_stock'] ||
           in_array($tool['status'], [TOOL_STATUS_EXPIRED, TOOL_STATUS_NEAR_EXPIRY]);
}

/**
 * Create automatic purchase order
 *
 * @param int $tool_id Tool ID
 * @param int $requested_by User ID
 * @return bool Success status
 */
function createAutoPurchaseOrder($tool_id, $requested_by) {
    try {
        // Get tool details
        $tool = dbQuery("SELECT * FROM tools WHERE tool_id = ?", [$tool_id]);
        if (empty($tool)) {
            return false;
        }
        $tool = $tool[0];

        // Check if pending order already exists
        $existing = dbQuery(
            "SELECT order_id FROM purchase_orders
             WHERE tool_id = ? AND status = ?
             LIMIT 1",
            [$tool_id, ORDER_STATUS_PENDING]
        );

        if (!empty($existing)) {
            return true; // Order already exists
        }

        // Calculate quantity to order
        $quantity = max(10, $tool['minimum_stock'] * 2);

        // Determine priority
        $priority = ORDER_PRIORITY_MEDIUM;
        if ($tool['current_stock'] == 0 || $tool['status'] === TOOL_STATUS_EXPIRED) {
            $priority = ORDER_PRIORITY_URGENT;
        } elseif ($tool['current_stock'] <= ($tool['minimum_stock'] / 2)) {
            $priority = ORDER_PRIORITY_HIGH;
        }

        // Create purchase order
        $sql = "INSERT INTO purchase_orders (tool_id, supplier_id, quantity, status, priority, requested_by, notes)
                VALUES (:tool_id, :supplier_id, :quantity, :status, :priority, :requested_by, :notes)";

        $params = [
            ':tool_id' => $tool_id,
            ':supplier_id' => $tool['supplier_id'],
            ':quantity' => $quantity,
            ':status' => ORDER_STATUS_PENDING,
            ':priority' => $priority,
            ':requested_by' => $requested_by,
            ':notes' => 'Automatically generated reorder'
        ];

        dbExecute($sql, $params);
        return true;

    } catch (Exception $e) {
        error_log("Auto Purchase Order Error: " . $e->getMessage());
        return false;
    }
}
