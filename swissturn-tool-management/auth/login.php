<?php
/**
 * Swissturn Tool Management System
 * Login Page and Handler
 *
 * This file handles user authentication
 */

define('SYSTEM_INIT', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $redirect = hasRole(ROLE_ADMIN) ? '/pages/admin/dashboard.php' : '/pages/worker/dashboard.php';
    header('Location: ' . BASE_URL . $redirect);
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Get user from database
            $sql = "SELECT user_id, username, password_hash, full_name, email, role, is_active
                    FROM users
                    WHERE username = :username
                    LIMIT 1";

            $result = dbQuery($sql, [':username' => $username]);

            if (!empty($result)) {
                $user = $result[0];

                // Check if account is active
                if (!$user['is_active']) {
                    $error = 'Your account has been deactivated. Please contact administrator.';
                }
                // Verify password
                elseif (verifyPassword($password, $user['password_hash'])) {
                    // Password correct - create session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];

                    // Log successful login
                    logAudit($user['user_id'], 'USER_LOGIN', 'users', $user['user_id']);

                    // Redirect based on role
                    $redirect = ($user['role'] === ROLE_ADMIN) ?
                        '/pages/admin/dashboard.php' :
                        '/pages/worker/dashboard.php';

                    header('Location: ' . BASE_URL . $redirect);
                    exit;
                } else {
                    $error = MSG_LOGIN_FAILED;
                }
            } else {
                $error = MSG_LOGIN_FAILED;
            }
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            $error = 'An error occurred during login. Please try again.';
        }
    }
}

// Get success message from query string (e.g., after logout)
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'logout') {
        $success = MSG_LOGOUT_SUCCESS;
    } elseif ($_GET['msg'] === 'session_expired') {
        $error = MSG_SESSION_EXPIRED;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/custom.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo and Title -->
            <div>
                <div class="flex justify-center">
                    <div class="bg-blue-600 rounded-full p-6">
                        <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path>
                        </svg>
                    </div>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    <?php echo APP_NAME; ?>
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Sign in to your account
                </p>
            </div>

            <!-- Error/Success Messages -->
            <?php if (!empty($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo e($error); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo e($success); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form class="mt-8 space-y-6" method="POST" action="">
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Username
                        </label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            required
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Enter your username"
                            value="<?php echo isset($_POST['username']) ? e($_POST['username']) : ''; ?>"
                            autofocus
                        >
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Enter your password"
                        >
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"/>
                            </svg>
                        </span>
                        Sign In
                    </button>
                </div>
            </form>

            <!-- Test Credentials Info -->
            <div class="text-center text-xs text-gray-500 mt-4 p-4 bg-gray-50 rounded">
                <p class="font-semibold mb-2">Test Credentials:</p>
                <p>Admin: <code class="bg-gray-200 px-2 py-1 rounded">admin / admin123</code></p>
                <p class="mt-1">Worker: <code class="bg-gray-200 px-2 py-1 rounded">worker1 / password123</code></p>
            </div>

            <!-- Footer -->
            <div class="text-center text-sm text-gray-600 mt-6">
                <p>&copy; 2025 Swissturn Tool Management. All rights reserved.</p>
                <p class="mt-1">Version <?php echo APP_VERSION; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
