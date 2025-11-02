<?php
/**
 * CREODENT Integrated Web Operations System
 * Dashboard Controller
 *
 * IMPORTANT: All charts and statistics MUST use date filters to prevent
 * infinite data loading. Default to last 30 days with user-selectable ranges.
 */

declare(strict_types=1);

class DashboardController {
    private CaseRepository $caseRepo;
    private CaseItemRepository $itemRepo;

    public function __construct() {
        $this->caseRepo = new CaseRepository();
        $this->itemRepo = new CaseItemRepository();
    }

    /**
     * Show dashboard
     */
    public function index(): void {
        // IMPORTANT: Enforce date range for statistics
        // Default to last 30 days, allow user to select 7, 30, or 90 days
        $daysBack = (int)($_GET['days'] ?? config('charts.default_days', 30));

        // Limit to max days from config
        $maxDays = config('charts.max_days', 90);
        if ($daysBack > $maxDays) {
            $daysBack = $maxDays;
        }

        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime("-{$daysBack} days"));

        // Get statistics for the date range
        $stats = $this->caseRepo->getDashboardStats($dateFrom, $dateTo);

        // Get recent cases (limited)
        $recentCases = $this->caseRepo->getRecent(10);

        // Get user's assigned items if worker/manager
        $user = currentUser();
        $myItems = [];

        if ($user && in_array($user['role'], ['worker', 'manager'])) {
            $myItems = $this->itemRepo->getByAssignedUser($user['id'], 'working');
        }

        // Get recent imports
        $recentImports = Database::fetchAll(
            "SELECT * FROM import_jobs ORDER BY started_at DESC LIMIT 5"
        );

        // Prepare data for view
        $data = [
            'stats' => $stats,
            'recent_cases' => $recentCases,
            'my_items' => $myItems,
            'recent_imports' => $recentImports,
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
                'days' => $daysBack,
            ],
        ];

        require VIEWS_PATH . '/dashboard/index.php';
    }

    /**
     * Get dashboard data as JSON (for AJAX updates)
     *
     * IMPORTANT: Always enforces date range limits
     */
    public function data(): void {
        $daysBack = (int)($_GET['days'] ?? config('charts.default_days', 30));
        $maxDays = config('charts.max_days', 90);

        if ($daysBack > $maxDays) {
            $daysBack = $maxDays;
        }

        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime("-{$daysBack} days"));

        $stats = $this->caseRepo->getDashboardStats($dateFrom, $dateTo);

        jsonResponse([
            'success' => true,
            'data' => $stats,
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
                'days' => $daysBack,
            ],
        ]);
    }
}
