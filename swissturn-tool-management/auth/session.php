<?php
/**
 * Swissturn Tool Management System
 * Session Management
 *
 * This file initializes and configures PHP sessions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings
    ini_set('session.cookie_httponly', SESSION_COOKIE_HTTPONLY);
    ini_set('session.cookie_samesite', SESSION_COOKIE_SAMESITE);

    if (SESSION_COOKIE_SECURE) {
        ini_set('session.cookie_secure', 1);
    }

    // Set session name
    session_name(SESSION_NAME);

    // Set session lifetime
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_set_cookie_params(SESSION_LIFETIME);

    // Start the session
    session_start();

    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) {
        // Regenerate session ID every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        // Session expired
        session_unset();
        session_destroy();
        session_start();
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
}
