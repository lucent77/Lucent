<?php
/**
 * CREODENT Integrated Web Operations System
 * Admin Controller
 *
 * Handles administrative functions:
 * - User management
 * - Department management
 * - Import/sync monitoring
 * - System settings
 */

declare(strict_types=1);

class AdminController {
    private UserRepository $userRepo;
    private DepartmentRepository $deptRepo;
    private AuthService $authService;

    public function __construct() {
        $this->userRepo = new UserRepository();
        $this->deptRepo = new DepartmentRepository();
        $this->authService = new AuthService();

        // Require admin role
        $this->authService->requireRole(['super_admin', 'admin']);
    }

    /**
     * Admin dashboard
     */
    public function index(): void {
        $stats = [
            'total_users' => Database::fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
            'total_cases' => Database::fetchOne("SELECT COUNT(*) as count FROM cases")['count'],
            'total_departments' => Database::fetchOne("SELECT COUNT(*) as count FROM departments WHERE is_active = 1")['count'],
        ];

        $data = [
            'stats' => $stats,
        ];

        require VIEWS_PATH . '/admin/index.php';
    }

    /**
     * User management - list users
     */
    public function users(): void {
        $users = $this->userRepo->getAll();
        $departments = $this->deptRepo->getAll();

        $data = [
            'users' => $users,
            'departments' => $departments,
        ];

        require VIEWS_PATH . '/admin/users.php';
    }

    /**
     * Create user
     */
    public function createUser(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }

        $data = [
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? null,
            'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
            'role' => $_POST['role'] ?? 'worker',
            'status' => $_POST['status'] ?? 'active',
        ];

        // Validate
        if (empty($data['username']) || empty($data['password']) || empty($data['full_name'])) {
            jsonResponse(['success' => false, 'error' => 'Username, password, and full name are required'], 400);
            return;
        }

        // Check if username exists
        if ($this->userRepo->usernameExists($data['username'])) {
            jsonResponse(['success' => false, 'error' => 'Username already exists'], 400);
            return;
        }

        try {
            $userId = $this->userRepo->create($data);

            jsonResponse([
                'success' => true,
                'user_id' => $userId,
                'message' => 'User created successfully',
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update user
     */
    public function updateUser(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }

        $data = [
            'full_name' => $_POST['full_name'] ?? null,
            'email' => $_POST['email'] ?? null,
            'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
            'role' => $_POST['role'] ?? null,
            'status' => $_POST['status'] ?? null,
        ];

        // Remove null values
        $data = array_filter($data, fn($v) => $v !== null);

        try {
            $this->userRepo->update($id, $data);

            jsonResponse([
                'success' => true,
                'message' => 'User updated successfully',
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete user (soft delete)
     */
    public function deleteUser(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }

        try {
            $this->userRepo->delete($id);

            jsonResponse([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Department management
     */
    public function departments(): void {
        $departments = $this->deptRepo->getAll(false); // Include inactive

        $data = [
            'departments' => $departments,
        ];

        require VIEWS_PATH . '/admin/departments.php';
    }

    /**
     * Create department
     */
    public function createDepartment(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }

        $data = [
            'code' => $_POST['code'] ?? '',
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if (empty($data['code']) || empty($data['name'])) {
            jsonResponse(['success' => false, 'error' => 'Code and name are required'], 400);
            return;
        }

        try {
            $deptId = $this->deptRepo->create($data);

            jsonResponse([
                'success' => true,
                'department_id' => $deptId,
                'message' => 'Department created successfully',
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update department
     */
    public function updateDepartment(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }

        $data = [
            'name' => $_POST['name'] ?? null,
            'description' => $_POST['description'] ?? null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        $data = array_filter($data, fn($v) => $v !== null);

        try {
            $this->deptRepo->update($id, $data);

            jsonResponse([
                'success' => true,
                'message' => 'Department updated successfully',
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Import/sync monitoring
     */
    public function imports(): void {
        // Get recent import jobs (limited to prevent infinite loading)
        $imports = Database::fetchAll(
            "SELECT * FROM import_jobs ORDER BY started_at DESC LIMIT 100"
        );

        $data = [
            'imports' => $imports,
        ];

        require VIEWS_PATH . '/admin/imports.php';
    }

    /**
     * Trigger manual Evolution import
     */
    public function triggerImport(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }

        try {
            // Call the cron import script
            $output = shell_exec('php ' . BASE_PATH . '/cron/import_evo.php 2>&1');

            jsonResponse([
                'success' => true,
                'message' => 'Import triggered successfully',
                'output' => $output,
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test Evolution connection
     */
    public function testEvolution(): void {
        try {
            $evoClient = new EvolutionClient();
            $connected = $evoClient->testConnection();

            jsonResponse([
                'success' => $connected,
                'message' => $connected ? 'Connection successful' : 'Connection failed',
            ]);
        } catch (Exception $e) {
            jsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
