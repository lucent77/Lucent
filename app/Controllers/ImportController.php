<?php
/**
 * Import Controller
 * Handles data import from Evolution Portal
 */

namespace App\Controllers;

use App\Core\Request;
use App\Services\ImportService;

class ImportController extends BaseController
{
    private ImportService $importService;

    public function __construct()
    {
        parent::__construct();
        $this->importService = new ImportService($this->config);
    }

    /**
     * Show import management page
     */
    public function index(): void
    {
        $this->requirePermission('import.run');

        $recentJobs = $this->importService->getRecentJobs(20);

        $this->view('admin.import', [
            'recent_jobs' => $recentJobs,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Test Evolution connection
     */
    public function testConnection(): void
    {
        $this->requirePermission('import.run');

        try {
            $result = $this->importService->testEvolutionConnection();

            $this->success($result, $result['message'] ?? 'Connection test completed');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Import cases for date range
     */
    public function importCases(): void
    {
        $this->requirePermission('import.run');
        $this->verifyCsrf();

        try {
            $startDate = Request::input('start_date');
            $endDate = Request::input('end_date');

            if (empty($startDate) || empty($endDate)) {
                throw new \InvalidArgumentException('Start date and end date are required');
            }

            $result = $this->importService->importFromEvolution($startDate, $endDate);

            $this->success($result, "Import completed: {$result['processed']} processed, {$result['failed']} failed");

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Sync recent cases
     */
    public function syncRecent(): void
    {
        $this->requirePermission('import.run');
        $this->verifyCsrf();

        try {
            $daysBack = (int)(Request::input('days_back') ?? 7);

            $result = $this->importService->syncRecentCases($daysBack);

            $this->success($result, "Sync completed: {$result['processed']} cases processed");

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Get import job details
     */
    public function jobDetails(int $id): void
    {
        $this->requirePermission('import.run');

        try {
            $job = $this->importService->getJobDetails($id);

            if (!$job) {
                throw new \RuntimeException('Job not found');
            }

            $this->success($job);

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
