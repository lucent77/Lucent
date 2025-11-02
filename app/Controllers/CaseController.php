<?php
/**
 * CREODENT Integrated Web Operations System
 * Case Controller
 *
 * Handles all case-related operations with proper optimistic locking
 * and concurrent edit protection
 */

declare(strict_types=1);

class CaseController {
    private CaseService $caseService;
    private CaseRepository $caseRepo;
    private DepartmentRepository $deptRepo;

    public function __construct() {
        $this->caseService = new CaseService();
        $this->caseRepo = new CaseRepository();
        $this->deptRepo = new DepartmentRepository();
    }

    /**
     * List all cases with pagination and filters
     */
    public function index(): void {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = config('pagination.per_page', 25);

        // Build filters from query string
        $filters = [
            'case_number' => $_GET['case_number'] ?? null,
            'patient_name' => $_GET['patient_name'] ?? null,
            'lab_name' => $_GET['lab_name'] ?? null,
            'status' => $_GET['status'] ?? null,
            'source' => $_GET['source'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ];

        // Remove empty filters
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

        // Get paginated cases
        $result = $this->caseRepo->getPaginated($filters, $page, $perPage);

        // Get departments for filter dropdown
        $departments = $this->deptRepo->getAll();

        $data = [
            'cases' => $result['data'],
            'pagination' => $result,
            'filters' => $filters,
            'departments' => $departments,
        ];

        require VIEWS_PATH . '/cases/index.php';
    }

    /**
     * Show case details
     *
     * @param int $id
     */
    public function show(int $id): void {
        $case = $this->caseService->getCaseDetails($id);

        if (!$case) {
            http_response_code(404);
            die('Case not found');
        }

        // Get departments for dropdown
        $departments = $this->deptRepo->getAll();

        // Get workers for assignment
        $userRepo = new UserRepository();
        $workers = $userRepo->getWorkersForAssignment();

        $data = [
            'case' => $case,
            'departments' => $departments,
            'workers' => $workers,
        ];

        require VIEWS_PATH . '/cases/show.php';
    }

    /**
     * Show create case form
     */
    public function create(): void {
        $departments = $this->deptRepo->getAll();

        $data = [
            'departments' => $departments,
        ];

        require VIEWS_PATH . '/cases/create.php';
    }

    /**
     * Store a new case
     */
    public function store(): void {
        $user = currentUser();

        $caseData = [
            'external_case_no' => $_POST['external_case_no'] ?? 'MANUAL-' . uniqid(),
            'patient_name' => $_POST['patient_name'] ?? null,
            'lab_name' => $_POST['lab_name'] ?? null,
            'location' => $_POST['location'] ?? null,
            'due_date' => $_POST['due_date'] ?? null,
            'status' => 'new',
            'source' => 'manual',
        ];

        $result = $this->caseService->createCase($caseData, $user['id']);

        if ($result['success']) {
            $_SESSION['flash_success'] = 'Case created successfully';
            redirect('/cases/' . $result['case_id']);
        } else {
            $_SESSION['flash_error'] = $result['error'];
            redirect('/cases/create');
        }
    }

    /**
     * Update case (AJAX endpoint)
     *
     * IMPORTANT: Implements optimistic locking to prevent concurrent edit conflicts
     */
    public function update(int $id): void {
        $user = currentUser();

        $version = (int)($_POST['version'] ?? 0);

        $data = [
            'patient_name' => $_POST['patient_name'] ?? null,
            'lab_name' => $_POST['lab_name'] ?? null,
            'location' => $_POST['location'] ?? null,
            'due_date' => $_POST['due_date'] ?? null,
            'status' => $_POST['status'] ?? null,
        ];

        // Remove null values
        $data = array_filter($data, fn($v) => $v !== null);

        $result = $this->caseService->updateCase($id, $data, $version, $user['id']);

        jsonResponse($result);
    }

    /**
     * Update case item (AJAX endpoint)
     *
     * IMPORTANT: Implements optimistic locking
     */
    public function updateItem(int $id): void {
        $user = currentUser();

        $version = (int)($_POST['version'] ?? 0);

        $data = [
            'tooth_no' => $_POST['tooth_no'] ?? null,
            'count' => isset($_POST['count']) ? (int)$_POST['count'] : null,
            'instruction' => $_POST['instruction'] ?? null,
            'preferences' => $_POST['preferences'] ?? null,
            'status' => $_POST['status'] ?? null,
            'assigned_to' => isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
        ];

        // Remove null values
        $data = array_filter($data, fn($v) => $v !== null && $v !== '');

        $result = $this->caseService->updateCaseItem($id, $data, $version, $user['id']);

        jsonResponse($result);
    }

    /**
     * Assign case item to worker (AJAX endpoint)
     */
    public function assignItem(int $itemId): void {
        $user = currentUser();

        $userId = (int)($_POST['user_id'] ?? 0);
        $version = (int)($_POST['version'] ?? 0);

        if (!$userId) {
            jsonResponse(['success' => false, 'error' => 'User ID required'], 400);
            return;
        }

        $result = $this->caseService->assignItem($itemId, $userId, $version, $user['id']);

        jsonResponse($result);
    }

    /**
     * Delete case
     */
    public function delete(int $id): void {
        $authService = new AuthService();

        if (!$authService->can('delete_cases')) {
            jsonResponse(['success' => false, 'error' => 'Permission denied'], 403);
            return;
        }

        $this->caseRepo->delete($id);

        jsonResponse(['success' => true]);
    }
}
