<?php
/**
 * Authentication and Authorization Manager
 */

namespace App\Core;

class Auth
{
    private static ?Auth $instance = null;
    private ?array $user = null;
    private array $config;

    /**
     * Private constructor
     */
    private function __construct(array $config)
    {
        $this->config = $config;
        $this->initializeSession();
        $this->loadUser();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(array $config = null): Auth
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new \RuntimeException('Config required for first initialization');
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Initialize session
     */
    private function initializeSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionConfig = $this->config['session'] ?? [];

            session_name($sessionConfig['name'] ?? 'CREODENT_SESSION');

            session_set_cookie_params([
                'lifetime' => $sessionConfig['lifetime'] ?? 7200,
                'path' => $sessionConfig['path'] ?? '/',
                'domain' => $sessionConfig['domain'] ?? '',
                'secure' => $sessionConfig['secure'] ?? true,
                'httponly' => $sessionConfig['httponly'] ?? true,
                'samesite' => $sessionConfig['samesite'] ?? 'Strict'
            ]);

            session_start();
        }
    }

    /**
     * Load user from session
     */
    private function loadUser(): void
    {
        if (isset($_SESSION['user_id'])) {
            $db = Database::getInstance();
            $this->user = $db->fetchOne(
                'SELECT id, username, name, email, role, department_id, status
                 FROM users WHERE id = ? AND status = ?',
                [$_SESSION['user_id'], 'active']
            );
        }
    }

    /**
     * Attempt to log in user
     */
    public function login(string $username, string $password): bool
    {
        $db = Database::getInstance();

        // Get user
        $user = $db->fetchOne(
            'SELECT * FROM users WHERE username = ? AND status = ?',
            [$username, 'active']
        );

        if (!$user) {
            return false;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Update last login
        $db->update('users',
            ['last_login_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user['id']]
        );

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Load user data
        unset($user['password_hash']);
        $this->user = $user;

        return true;
    }

    /**
     * Log out user
     */
    public function logout(): void
    {
        $this->user = null;
        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();
    }

    /**
     * Check if user is authenticated
     */
    public function check(): bool
    {
        return $this->user !== null;
    }

    /**
     * Check if user is guest (not authenticated)
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get authenticated user
     */
    public function user(): ?array
    {
        return $this->user;
    }

    /**
     * Get user ID
     */
    public function id(): ?int
    {
        return $this->user['id'] ?? null;
    }

    /**
     * Get user role
     */
    public function role(): ?string
    {
        return $this->user['role'] ?? null;
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $role): bool
    {
        return $this->check() && $this->user['role'] === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->check() && in_array($this->user['role'], $roles);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user is admin or super admin
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Check if user has permission
     */
    public function can(string $permission): bool
    {
        if (!$this->check()) {
            return false;
        }

        $roleConfig = $this->config['roles'][$this->user['role']] ?? null;

        if (!$roleConfig) {
            return false;
        }

        $permissions = $roleConfig['permissions'] ?? [];

        // Super admin has all permissions
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Check if user cannot perform action
     */
    public function cannot(string $permission): bool
    {
        return !$this->can($permission);
    }

    /**
     * Require authentication or redirect
     */
    public function requireAuth(string $redirectUrl = '/login'): void
    {
        if ($this->guest()) {
            header("Location: $redirectUrl");
            exit;
        }
    }

    /**
     * Require permission or show 403
     */
    public function requirePermission(string $permission): void
    {
        if ($this->cannot($permission)) {
            http_response_code(403);
            die('Access Denied');
        }
    }

    /**
     * Require role or show 403
     */
    public function requireRole(string $role): void
    {
        if (!$this->hasRole($role)) {
            http_response_code(403);
            die('Access Denied - Insufficient privileges');
        }
    }

    /**
     * Generate CSRF token
     */
    public function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        $lifetime = $this->config['security']['csrf_token_lifetime'] ?? 3600;
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;

        // Check if token has expired
        if (time() - $tokenTime > $lifetime) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Hash password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Get user's department
     */
    public function department(): ?array
    {
        if (!$this->check() || !$this->user['department_id']) {
            return null;
        }

        $db = Database::getInstance();
        return $db->fetchOne(
            'SELECT * FROM departments WHERE id = ?',
            [$this->user['department_id']]
        );
    }

    /**
     * Check if user belongs to department
     */
    public function belongsToDepartment(int $departmentId): bool
    {
        return $this->check() && $this->user['department_id'] == $departmentId;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \RuntimeException('Cannot unserialize singleton');
    }
}
