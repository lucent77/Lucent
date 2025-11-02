<?php
/**
 * Dashboard Controller
 * Handles dashboard and home page
 */

namespace App\Controllers;

use App\Services\CaseService;
use App\Repositories\CaseItemRepository;

class DashboardController extends BaseController
{
    private CaseService $caseService;
    private CaseItemRepository $itemRepo;

    public function __construct()
    {
        parent::__construct();
        $this->caseService = new CaseService();
        $this->itemRepo = new CaseItemRepository();
    }

    /**
     * Show dashboard
     */
    public function index(): void
    {
        $this->requireAuth();

        $dashboardData = $this->caseService->getDashboardData();

        // Get user's assigned work
        $userWork = $this->caseService->getUserWork($this->auth->id());

        $this->view('dashboard.index', [
            'stats' => $dashboardData['stats'],
            'department_workload' => $dashboardData['department_workload'],
            'due_today' => $dashboardData['due_today'],
            'overdue' => $dashboardData['overdue'],
            'recent_cases' => $dashboardData['recent_cases'],
            'my_assigned_items' => $userWork['assigned_items'],
            'my_workload' => $userWork['workload']
        ]);
    }

    /**
     * Get dashboard stats (API)
     */
    public function stats(): void
    {
        $this->requireAuth();

        $dashboardData = $this->caseService->getDashboardData();

        $this->success($dashboardData);
    }

    /**
     * Get my work (API)
     */
    public function myWork(): void
    {
        $this->requireAuth();

        $userWork = $this->caseService->getUserWork($this->auth->id());

        $this->success($userWork);
    }
}
