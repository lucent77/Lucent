<?php
/**
 * CREODENT Integrated Web Operations System
 * Authentication Controller
 */

declare(strict_types=1);

class AuthController {
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    /**
     * Show login page
     */
    public function index(): void {
        // If already logged in, redirect to dashboard
        if (isLoggedIn()) {
            redirect('/dashboard');
            return;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password';
            } else {
                $result = $this->authService->login($username, $password);

                if ($result['success']) {
                    redirect('/dashboard');
                    return;
                } else {
                    $error = $result['error'];
                }
            }
        }

        // Show login form
        require VIEWS_PATH . '/auth/login.php';
    }

    /**
     * Handle logout
     */
    public function logout(): void {
        $this->authService->logout();
        redirect('/login');
    }
}
