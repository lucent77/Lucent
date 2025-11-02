<?php
/**
 * CREODENT Integrated Web Operations System
 * Authentication Service
 */

declare(strict_types=1);

class AuthService {
    private UserRepository $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    /**
     * Attempt to log in a user
     *
     * @param string $username
     * @param string $password
     * @return array ['success' => bool, 'error' => string, 'user' => array]
     */
    public function login(string $username, string $password): array {
        $user = $this->userRepo->verifyCredentials($username, $password);

        if (!$user) {
            return [
                'success' => false,
                'error' => 'Invalid username or password',
            ];
        }

        // Update last login
        $this->userRepo->updateLastLogin($user['id']);

        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'department_id' => $user['department_id'],
            'department_name' => $user['department_name'],
            'department_code' => $user['department_code'],
        ];
        $_SESSION['last_regeneration'] = time();

        return [
            'success' => true,
            'user' => $_SESSION['user'],
        ];
    }

    /**
     * Log out the current user
     */
    public function logout(): void {
        // Destroy session
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Check if user has permission for an action
     *
     * @param string $action
     * @param array|null $resource
     * @return bool
     */
    public function can(string $action, ?array $resource = null): bool {
        $user = currentUser();

        if (!$user) {
            return false;
        }

        // Super admin can do everything
        if ($user['role'] === 'super_admin') {
            return true;
        }

        // Admin can do most things
        if ($user['role'] === 'admin') {
            return !in_array($action, ['manage_users', 'manage_system']);
        }

        // Manager permissions
        if ($user['role'] === 'manager') {
            $managerActions = ['view_cases', 'edit_cases', 'assign_work', 'view_department'];

            if (in_array($action, $managerActions)) {
                // Check if resource belongs to their department
                if ($resource && isset($resource['department_id'])) {
                    return $resource['department_id'] == $user['department_id'];
                }
                return true;
            }

            return false;
        }

        // Worker permissions
        if ($user['role'] === 'worker') {
            $workerActions = ['view_cases', 'edit_assigned_work'];

            if (in_array($action, $workerActions)) {
                // Workers can only edit their own assigned work
                if ($action === 'edit_assigned_work' && $resource && isset($resource['assigned_to'])) {
                    return $resource['assigned_to'] == $user['id'];
                }

                // Check department
                if ($resource && isset($resource['department_id'])) {
                    return $resource['department_id'] == $user['department_id'];
                }

                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * Require authentication
     *
     * @return bool
     */
    public function requireAuth(): bool {
        if (!isLoggedIn()) {
            redirect('/login');
            return false;
        }
        return true;
    }

    /**
     * Require specific role
     *
     * @param array $roles
     * @return bool
     */
    public function requireRole(array $roles): bool {
        if (!isLoggedIn()) {
            redirect('/login');
            return false;
        }

        if (!hasAnyRole($roles)) {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }

        return true;
    }
}
