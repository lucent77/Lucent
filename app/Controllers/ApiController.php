<?php
/**
 * CREODENT Integrated Web Operations System
 * API Controller
 *
 * Provides RESTful API endpoints for external integrations
 * and AJAX requests
 */

declare(strict_types=1);

class ApiController {
    /**
     * API authentication check
     */
    private function requireApiAuth(): bool {
        // For now, use session auth
        // Later can add API key authentication
        return isLoggedIn();
    }

    /**
     * Get case by external case number
     */
    public function getCase(): void {
        if (!$this->requireApiAuth()) {
            jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            return;
        }

        $caseNumber = $_GET['case_number'] ?? null;

        if (!$caseNumber) {
            jsonResponse(['success' => false, 'error' => 'case_number parameter required'], 400);
            return;
        }

        $caseRepo = new CaseRepository();
        $case = $caseRepo->findByExternalCaseNo($caseNumber);

        if (!$case) {
            jsonResponse(['success' => false, 'error' => 'Case not found'], 404);
            return;
        }

        // Get items
        $itemRepo = new CaseItemRepository();
        $case['items'] = $itemRepo->getByCaseId($case['id']);

        jsonResponse([
            'success' => true,
            'data' => $case,
        ]);
    }

    /**
     * Create case via API
     */
    public function createCase(): void {
        if (!$this->requireApiAuth()) {
            jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            jsonResponse(['success' => false, 'error' => 'Invalid JSON'], 400);
            return;
        }

        $caseService = new CaseService();
        $user = currentUser();

        $result = $caseService->createCase($input, $user['id']);

        jsonResponse($result);
    }

    /**
     * Update case via API
     */
    public function updateCase(): void {
        if (!$this->requireApiAuth()) {
            jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            return;
        }

        $caseId = (int)($_GET['id'] ?? 0);

        if (!$caseId) {
            jsonResponse(['success' => false, 'error' => 'Case ID required'], 400);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            jsonResponse(['success' => false, 'error' => 'Invalid JSON'], 400);
            return;
        }

        $version = (int)($input['version'] ?? 0);
        unset($input['version']);

        $caseService = new CaseService();
        $user = currentUser();

        $result = $caseService->updateCase($caseId, $input, $version, $user['id']);

        jsonResponse($result);
    }

    /**
     * Health check endpoint
     */
    public function health(): void {
        $dbConnected = false;

        try {
            Database::getConnection();
            $dbConnected = true;
        } catch (Exception $e) {
            // DB not connected
        }

        jsonResponse([
            'success' => true,
            'status' => 'healthy',
            'database' => $dbConnected ? 'connected' : 'disconnected',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
}
