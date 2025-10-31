<?php
/**
 * Swissturn Tool Management System
 * Main Entry Point
 *
 * This file redirects users to the appropriate page based on their authentication status
 */

define('SYSTEM_INIT', true);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth/session.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect based on role
    if (hasRole(ROLE_ADMIN)) {
        header('Location: ' . BASE_URL . '/pages/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/pages/worker/dashboard.php');
    }
    exit;
} else {
    // Not logged in, redirect to login page
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
