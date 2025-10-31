<?php
/**
 * Swissturn Tool Management System
 * Logout Handler
 *
 * This file handles user logout
 */

define('SYSTEM_INIT', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/session.php';

// Log logout action if user is logged in
if (isLoggedIn()) {
    logAudit(getCurrentUserId(), 'USER_LOGOUT', 'users', getCurrentUserId());
}

// Clear all session variables
$_SESSION = [];

// Delete session cookie
if (isset($_COOKIE[SESSION_NAME])) {
    setcookie(SESSION_NAME, '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to login page with success message
header('Location: ' . BASE_URL . '/auth/login.php?msg=logout');
exit;
