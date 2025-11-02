<?php
/**
 * Case Controller
 * Handles case management operations
 */

namespace App\Controllers;

use App\Core\Request;
use App\Services\CaseService;
use App\Repositories\CaseRepository;
use App\Repositories\CaseItemRepository;
use App\Repositories\DepartmentRepository;

class CaseController extends BaseController
{
    private CaseService $caseService;
    private CaseRepository $caseRepo;
    private CaseItemRepository $itemRepo;
    private DepartmentRepository $deptRepo;

    public function __construct()
    {
        parent::__construct();
        $this->caseService = new CaseService();
        $this->caseRepo = new CaseRepository();
        $this->itemRepo = new CaseItemRepository();
        $this->deptRepo = new DepartmentRepository();
    }

    /**
     * Show case list
     */
    public function index(): void
    {
        $this->requirePermission('cases.view');

        $page = (int)(Request::query('page') ?? 1);
        $perPage = (int)(Request::query('per_page') ?? 25);

        $filters = Request::only([
            'status', 'location', 'lab_name', 'patient_name',
            'case_no', 'due_date_from', 'due_date_to'
        ]);

        $result = $this->caseRepo->getAll($filters, $page, $perPage);
        $departments = $this->deptRepo->getAll();

        $this->view('cases.index', [
            'cases' => $result['data'],
            'pagination' => $result,
            'filters' => $filters,
            'departments' => $departments,
            'statuses' => $this->config['statuses']['case']
        ]);
    }

    /**
     * Show case detail
     */
    public function show(int $id): void
    {
        $this->requirePermission('cases.view');

        $case = $this->caseService->getCaseWithItems($id);

        if (!$case) {
            http_response_code(404);
            $this->view('errors.404');
            return;
        }

        $departments = $this->deptRepo->getAll();

        $this->view('cases.show', [
            'case' => $case,
            'departments' => $departments,
            'statuses' => $this->config['statuses'],
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Show create case form
     */
    public function create(): void
    {
        $this->requirePermission('cases.create');

        $departments = $this->deptRepo->getAll();

        $this->view('cases.create', [
            'departments' => $departments,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Store new case
     */
    public function store(): void
    {
        $this->requirePermission('cases.create');
        $this->verifyCsrf();

        try {
            $data = Request::only([
                'external_case_no', 'patient_name', 'lab_name', 'account_name',
                'due_date', 'location', 'status'
            ]);

            $data['source'] = 'manual';

            // Get items if provided
            $items = Request::input('items') ?? [];

            $caseId = $this->caseService->createCase($data, $items, $this->auth->id());

            if (Request::isAjax()) {
                $this->success(['id' => $caseId], 'Case created successfully');
            } else {
                $this->redirect("/cases/$caseId");
            }

        } catch (\Exception $e) {
            if (Request::isAjax()) {
                $this->error($e->getMessage());
            } else {
                $_SESSION['error'] = $e->getMessage();
                $this->redirect('/cases/create');
            }
        }
    }

    /**
     * Update case
     */
    public function update(int $id): void
    {
        $this->requirePermission('cases.edit');
        $this->verifyCsrf();

        try {
            $data = Request::only([
                'patient_name', 'lab_name', 'account_name',
                'due_date', 'location', 'status'
            ]);

            $version = (int)Request::input('version');

            $this->caseService->updateCase($id, $data, $version, $this->auth->id());

            $this->success(null, 'Case updated successfully');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Update case status
     */
    public function updateStatus(int $id): void
    {
        $this->requirePermission('cases.edit');
        $this->verifyCsrf();

        try {
            $status = Request::input('status');
            $version = (int)Request::input('version');

            if (!in_array($status, array_keys($this->config['statuses']['case']))) {
                throw new \InvalidArgumentException('Invalid status');
            }

            $this->caseService->updateCaseStatus($id, $status, $version, $this->auth->id());

            $this->success(null, 'Status updated successfully');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Delete case
     */
    public function delete(int $id): void
    {
        $this->requirePermission('cases.delete');
        $this->verifyCsrf();

        try {
            $this->caseService->deleteCase($id, $this->auth->id());

            $this->success(null, 'Case deleted successfully');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Assign item to user
     */
    public function assignItem(): void
    {
        $this->requirePermission('cases.assign');
        $this->verifyCsrf();

        try {
            $itemId = (int)Request::input('item_id');
            $userId = (int)Request::input('user_id');
            $version = (int)Request::input('version');

            $this->caseService->assignItem($itemId, $userId, $version, $this->auth->id());

            $this->success(null, 'Item assigned successfully');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Update item status
     */
    public function updateItemStatus(): void
    {
        $this->requirePermission('cases.update_status');
        $this->verifyCsrf();

        try {
            $itemId = (int)Request::input('item_id');
            $status = Request::input('status');
            $version = (int)Request::input('version');

            if (!in_array($status, array_keys($this->config['statuses']['item']))) {
                throw new \InvalidArgumentException('Invalid status');
            }

            $this->caseService->updateItemStatus($itemId, $status, $version, $this->auth->id());

            $this->success(null, 'Item status updated successfully');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Bulk assign items
     */
    public function bulkAssign(): void
    {
        $this->requirePermission('cases.assign');
        $this->verifyCsrf();

        try {
            $itemIds = Request::input('item_ids') ?? [];
            $userId = (int)Request::input('user_id');

            if (empty($itemIds) || !$userId) {
                throw new \InvalidArgumentException('Invalid parameters');
            }

            $result = $this->caseService->bulkAssignItems($itemIds, $userId, $this->auth->id());

            $this->success($result, 'Bulk assignment completed');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Search cases
     */
    public function search(): void
    {
        $this->requirePermission('cases.view');

        $query = Request::input('q');
        $page = (int)(Request::query('page') ?? 1);

        if (empty($query)) {
            $this->redirect('/cases');
            return;
        }

        $result = $this->caseRepo->search($query, $page);

        if (Request::isAjax()) {
            $this->success($result);
        } else {
            $this->view('cases.index', [
                'cases' => $result['data'],
                'pagination' => $result,
                'search_query' => $query
            ]);
        }
    }
}
