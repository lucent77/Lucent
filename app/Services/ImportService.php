<?php
/**
 * Import Service
 * Handles importing data from Evolution Portal and Google Sheets
 */

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Repositories\CaseRepository;
use App\Repositories\AuditLogRepository;

class ImportService
{
    private Database $db;
    private Logger $logger;
    private EvolutionClient $evoClient;
    private CaseRepository $caseRepo;
    private AuditLogRepository $auditRepo;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->db = Database::getInstance();
        $this->logger = new Logger($config['logging']['path'] ?? __DIR__ . '/../../logs', 'import');
        $this->evoClient = new EvolutionClient($config);
        $this->caseRepo = new CaseRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Import cases from Evolution Portal for date range
     */
    public function importFromEvolution(string $startDate, string $endDate): array
    {
        $jobId = $this->createImportJob('evo_case_list');

        $this->logger->info("Starting Evolution import for date range: $startDate to $endDate");

        try {
            // Get case list
            $response = $this->evoClient->getCaseList($startDate, $endDate);

            if (!$response['success']) {
                throw new \RuntimeException($response['message']);
            }

            $cases = $response['data']['cases'] ?? [];

            if (!is_array($cases)) {
                $cases = [$cases]; // Handle single case
            }

            $processed = 0;
            $failed = 0;
            $imported = [];
            $errors = [];

            foreach ($cases as $caseData) {
                try {
                    $caseId = $this->processEvolutionCase($caseData, $response['raw_response']);
                    $imported[] = $caseId;
                    $processed++;

                    $this->logger->info("Imported case: " . ($caseData['case_number'] ?? 'unknown'));

                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'case' => $caseData['case_number'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];

                    $this->logger->error("Failed to import case: " . $e->getMessage(), [
                        'case_data' => $caseData
                    ]);
                }
            }

            $this->completeImportJob($jobId, 'success', [
                'message' => "Processed: $processed, Failed: $failed",
                'imported' => $imported,
                'errors' => $errors
            ], $processed, $failed);

            return [
                'success' => true,
                'processed' => $processed,
                'failed' => $failed,
                'imported' => $imported,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            $this->completeImportJob($jobId, 'error', [
                'error' => $e->getMessage()
            ]);

            $this->logger->error("Evolution import failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process single case from Evolution
     */
    private function processEvolutionCase(array $caseData, string $rawXml): int
    {
        $this->db->beginTransaction();

        try {
            // Map Evolution data to our structure
            $mappedData = [
                'external_case_no' => $caseData['case_number'] ?? $caseData['casenumber'] ?? 'UNKNOWN',
                'source' => 'evolution_web_portal',
                'patient_name' => $caseData['patient_name'] ?? $caseData['patient'] ?? null,
                'lab_name' => $caseData['lab_name'] ?? $caseData['laboratory'] ?? null,
                'account_name' => $caseData['account_name'] ?? null,
                'due_date' => $this->parseDate($caseData['due_date'] ?? $caseData['duedate'] ?? null),
                'location' => $this->mapLocation($caseData['location'] ?? 'HV'),
                'status' => 'new',
                'raw_payload' => $rawXml
            ];

            // Upsert case
            $caseId = $this->caseRepo->upsert($mappedData);

            // Log import
            $this->auditRepo->log(
                $caseId,
                null,
                'import_from_evo',
                'Case imported from Evolution Portal',
                null,
                $mappedData
            );

            $this->db->commit();

            return $caseId;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Import detailed case information
     */
    public function importCaseDetails(string $caseNumber): array
    {
        $this->logger->info("Importing detailed information for case: $caseNumber");

        try {
            $response = $this->evoClient->getCaseInformation($caseNumber);

            if (!$response['success']) {
                throw new \RuntimeException($response['message']);
            }

            $caseData = $response['data'];

            // Find existing case
            $case = $this->caseRepo->findByExternalCaseNo($caseNumber);

            if (!$case) {
                throw new \RuntimeException("Case not found: $caseNumber");
            }

            // Update with detailed information
            $updateData = [
                'raw_payload' => $response['raw_response']
            ];

            // Extract additional fields from detailed data if available
            if (isset($caseData['notes'])) {
                $updateData['preferences'] = $caseData['notes'];
            }

            $this->caseRepo->update($case['id'], $updateData, $case['version']);

            $this->logger->info("Updated case details for: $caseNumber");

            return [
                'success' => true,
                'case_id' => $case['id'],
                'message' => 'Case details imported successfully'
            ];

        } catch (\Exception $e) {
            $this->logger->error("Failed to import case details: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create import job record
     */
    private function createImportJob(string $jobType): int
    {
        return $this->db->insert('import_jobs', [
            'job_type' => $jobType,
            'started_at' => date('Y-m-d H:i:s'),
            'status' => 'running'
        ]);
    }

    /**
     * Complete import job
     */
    private function completeImportJob(
        int $jobId,
        string $status,
        array $message = [],
        int $processed = 0,
        int $failed = 0
    ): void {
        $this->db->update('import_jobs', [
            'ended_at' => date('Y-m-d H:i:s'),
            'status' => $status,
            'message' => json_encode($message),
            'records_processed' => $processed,
            'records_failed' => $failed
        ], 'id = :id', ['id' => $jobId]);
    }

    /**
     * Get recent import jobs
     */
    public function getRecentJobs(int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM import_jobs ORDER BY started_at DESC LIMIT ?',
            [$limit]
        );
    }

    /**
     * Parse date from various formats
     */
    private function parseDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            $dt = new \DateTime($date);
            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            $this->logger->warning("Failed to parse date: $date");
            return null;
        }
    }

    /**
     * Map location codes
     */
    private function mapLocation(string $location): string
    {
        $map = [
            'HV' => 'HV',
            'NYC' => 'NYC',
            'HVNYC' => 'HVNYC',
            'NYCHV' => 'NYCHV',
            'HUDSON' => 'HV',
            'NEW YORK' => 'NYC'
        ];

        $location = strtoupper(trim($location));

        return $map[$location] ?? 'HV';
    }

    /**
     * Test Evolution Portal connection
     */
    public function testEvolutionConnection(): array
    {
        return $this->evoClient->testConnection();
    }

    /**
     * Sync recent cases (for cron job)
     */
    public function syncRecentCases(int $daysBack = 7): array
    {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-$daysBack days"));

        $this->logger->info("Syncing recent cases from last $daysBack days");

        return $this->importFromEvolution($startDate, $endDate);
    }
}
