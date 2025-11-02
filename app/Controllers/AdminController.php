<?php
/**
 * Admin Controller
 * Handles administrative functions
 */

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\UserRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\AuditLogRepository;

class AdminController extends BaseController
{
    private UserRepository $userRepo;
    private DepartmentRepository $deptRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        parent::__construct();
        $this->userRepo = new UserRepository();
        $this->deptRepo = new DepartmentRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Admin dashboard
     */
    public function index(): void
    {
        $this->requireRole('admin');

        $this->view('admin.index', [
            'users_count' => count($this->userRepo->getAll(['status' => 'active'])),
            'departments_count' => count($this->deptRepo->getAll()),
            'recent_logs' => $this->auditRepo->getRecent(10)
        ]);
    }

    /**
     * User management - list
     */
    public function users(): void
    {
        $this->requirePermission('users.view');

        $users = $this->userRepo->getAll();
        $departments = $this->deptRepo->getAll();

        $this->view('admin.users', [
            'users' => $users,
            'departments' => $departments,
            'roles' => $this->config['roles'],
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Create user
     */
    public function createUser(): void
    {
        $this->requirePermission('users.create');
        $this->verifyCsrf();

        try {
            $data = Request::only(['username', 'password', 'name', 'email', 'department_id', 'role']);

            if (empty($data['username']) || empty($data['password']) || empty($data['name'])) {
                throw new \InvalidArgumentException('Username, password, and name are required');
            }

            $userId = $this->userRepo->create($data);

            $this->success(['id' => $userId], 'User created successfully');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Update user
     */
    public function updateUser(int $id): void
    {
        $this->requirePermission('users.edit');
        $this->verifyCsrf();

        try {
            $data = Request::only(['name', 'email', 'department_id', 'role', 'status', 'password']);

            // Remove empty password
            if (empty($data['password'])) {
                unset($data['password']);
            }

            $this->userRepo->update($id, $data);

            $this->success(null, 'User updated successfully');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(int $id): void
    {
        $this->requirePermission('users.edit');
        $this->verifyCsrf();

        try {
            // Prevent deleting yourself
            if ($id === $this->auth->id()) {
                throw new \RuntimeException('Cannot delete your own account');
            }

            $this->userRepo->delete($id);

            $this->success(null, 'User deleted successfully');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Department management
     */
    public function departments(): void
    {
        $this->requirePermission('departments.view');

        $departments = $this->deptRepo->getAll(false);

        $this->view('admin.departments', [
            'departments' => $departments,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Create department
     */
    public function createDepartment(): void
    {
        $this->requireRole('super_admin');
        $this->verifyCsrf();

        try {
            $data = Request::only(['code', 'name', 'description']);

            if (empty($data['code']) || empty($data['name'])) {
                throw new \InvalidArgumentException('Code and name are required');
            }

            $deptId = $this->deptRepo->create($data);

            $this->success(['id' => $deptId], 'Department created successfully');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Audit logs
     */
    public function auditLogs(): void
    {
        $this->requireRole('admin');

        $logs = $this->auditRepo->getRecent(100);

        $this->view('admin.audit-logs', [
            'logs' => $logs
        ]);
    }
}
