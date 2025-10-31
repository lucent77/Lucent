<?php
/**
 * Swissturn Tool Management System
 * Users API Endpoint
 *
 * Handles user management (Admin only)
 */

define('SYSTEM_INIT', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../auth/session.php';

header('Content-Type: application/json');

// Require admin role for all operations
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet();
            break;

        case 'POST':
            handlePost();
            break;

        case 'PUT':
            handlePut();
            break;

        case 'DELETE':
            handleDelete();
            break;

        default:
            jsonResponse(false, null, 'Method not allowed', HTTP_METHOD_NOT_ALLOWED);
    }
} catch (Exception $e) {
    error_log("Users API Error: " . $e->getMessage());
    jsonResponse(false, null, MSG_ERROR_DATABASE, HTTP_INTERNAL_ERROR);
}

/**
 * GET - Retrieve users
 */
function handleGet() {
    if (isset($_GET['id'])) {
        // Get single user
        $user_id = intval($_GET['id']);
        $sql = "SELECT user_id, username, full_name, email, role, is_active, created_at, updated_at
                FROM users
                WHERE user_id = ?";

        $result = dbQuery($sql, [$user_id]);

        if (empty($result)) {
            jsonResponse(false, null, 'User not found', HTTP_NOT_FOUND);
        }

        jsonResponse(true, $result[0], 'User retrieved successfully');

    } else {
        // Get all users
        $where = [];
        $params = [];

        if (isset($_GET['role'])) {
            $where[] = "role = ?";
            $params[] = $_GET['role'];
        }

        if (isset($_GET['is_active'])) {
            $where[] = "is_active = ?";
            $params[] = intval($_GET['is_active']);
        }

        $sql = "SELECT user_id, username, full_name, email, role, is_active, created_at, updated_at
                FROM users";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY full_name ASC";

        $users = dbQuery($sql, $params);

        jsonResponse(true, $users, 'Users retrieved successfully');
    }
}

/**
 * POST - Create new user
 */
function handlePost() {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    $validation = validateUserData($data, false);
    if (!$validation['valid']) {
        jsonResponse(false, null, implode(' ', $validation['errors']), HTTP_BAD_REQUEST);
    }

    // Check if username exists
    $existing = dbQuery("SELECT user_id FROM users WHERE username = ?", [$data['username']]);
    if (!empty($existing)) {
        jsonResponse(false, null, 'Username already exists', HTTP_BAD_REQUEST);
    }

    // Check if email exists
    $existing = dbQuery("SELECT user_id FROM users WHERE email = ?", [$data['email']]);
    if (!empty($existing)) {
        jsonResponse(false, null, 'Email already exists', HTTP_BAD_REQUEST);
    }

    // Hash password
    $password_hash = hashPassword($data['password']);

    // Insert user
    $sql = "INSERT INTO users (username, password_hash, full_name, email, role, is_active)
            VALUES (:username, :password_hash, :full_name, :email, :role, :is_active)";

    $params = [
        ':username' => $data['username'],
        ':password_hash' => $password_hash,
        ':full_name' => $data['full_name'],
        ':email' => $data['email'],
        ':role' => $data['role'],
        ':is_active' => isset($data['is_active']) ? intval($data['is_active']) : 1
    ];

    dbExecute($sql, $params);
    $user_id = dbLastInsertId();

    // Log action (without password)
    $log_data = $data;
    unset($log_data['password']);
    logAudit(getCurrentUserId(), 'CREATE_USER', 'users', $user_id, null, $log_data);

    jsonResponse(true, ['user_id' => $user_id], MSG_USER_CREATED, HTTP_CREATED);
}

/**
 * PUT - Update user
 */
function handlePut() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['user_id'])) {
        jsonResponse(false, null, 'User ID is required', HTTP_BAD_REQUEST);
    }

    $user_id = intval($data['user_id']);

    // Get old values
    $old_user = dbQuery("SELECT * FROM users WHERE user_id = ?", [$user_id]);
    if (empty($old_user)) {
        jsonResponse(false, null, 'User not found', HTTP_NOT_FOUND);
    }
    $old_user = $old_user[0];

    // Validate input
    $validation = validateUserData($data, true);
    if (!$validation['valid']) {
        jsonResponse(false, null, implode(' ', $validation['errors']), HTTP_BAD_REQUEST);
    }

    // Check if username exists for other users
    $existing = dbQuery("SELECT user_id FROM users WHERE username = ? AND user_id != ?",
                        [$data['username'], $user_id]);
    if (!empty($existing)) {
        jsonResponse(false, null, 'Username already exists', HTTP_BAD_REQUEST);
    }

    // Check if email exists for other users
    $existing = dbQuery("SELECT user_id FROM users WHERE email = ? AND user_id != ?",
                        [$data['email'], $user_id]);
    if (!empty($existing)) {
        jsonResponse(false, null, 'Email already exists', HTTP_BAD_REQUEST);
    }

    // Build update query
    $sql = "UPDATE users SET
                username = :username,
                full_name = :full_name,
                email = :email,
                role = :role,
                is_active = :is_active,
                updated_at = NOW()";

    $params = [
        ':username' => $data['username'],
        ':full_name' => $data['full_name'],
        ':email' => $data['email'],
        ':role' => $data['role'],
        ':is_active' => isset($data['is_active']) ? intval($data['is_active']) : 1,
        ':user_id' => $user_id
    ];

    // Update password if provided
    if (!empty($data['password'])) {
        $sql .= ", password_hash = :password_hash";
        $params[':password_hash'] = hashPassword($data['password']);
    }

    $sql .= " WHERE user_id = :user_id";

    dbExecute($sql, $params);

    // Log action (without password)
    $log_data = $data;
    unset($log_data['password']);
    logAudit(getCurrentUserId(), 'UPDATE_USER', 'users', $user_id, $old_user, $log_data);

    jsonResponse(true, ['user_id' => $user_id], MSG_USER_UPDATED);
}

/**
 * DELETE - Delete user
 */
function handleDelete() {
    if (!isset($_GET['id'])) {
        jsonResponse(false, null, 'User ID is required', HTTP_BAD_REQUEST);
    }

    $user_id = intval($_GET['id']);

    // Prevent deleting self
    if ($user_id === getCurrentUserId()) {
        jsonResponse(false, null, 'Cannot delete your own account', HTTP_BAD_REQUEST);
    }

    // Check if user exists
    $user = dbQuery("SELECT * FROM users WHERE user_id = ?", [$user_id]);
    if (empty($user)) {
        jsonResponse(false, null, 'User not found', HTTP_NOT_FOUND);
    }

    // Delete user
    dbExecute("DELETE FROM users WHERE user_id = ?", [$user_id]);

    // Log action
    logAudit(getCurrentUserId(), 'DELETE_USER', 'users', $user_id, $user[0], null);

    jsonResponse(true, null, MSG_USER_DELETED);
}
