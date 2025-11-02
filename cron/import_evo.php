<?php
/**
 * CREODENT Integrated Web Operations System
 * Evolution Web Portal Import Cron Job
 *
 * This script should be run regularly (e.g., every hour) to import
 * new cases from the Evolution Web Portal.
 *
 * Setup cron (example for hourly):
 * 0 * * * * /usr/bin/php /path/to/project/cron/import_evo.php >> /path/to/project/storage/logs/import.log 2>&1
 *
 * IMPORTANT: This script uses date ranges to prevent loading infinite data.
 * By default, it imports cases from the last 7 days on each run.
 */

declare(strict_types=1);

// Bootstrap
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');

// Load configuration
$config = require CONFIG_PATH . '/config.php';
date_default_timezone_set($config['app']['timezone']);

// Autoloader
spl_autoload_register(function ($class) {
    $base_dir = APP_PATH . '/';
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Make config globally available
$GLOBALS['config'] = $config;

function config(string $key = null, $default = null) {
    if ($key === null) {
        return $GLOBALS['config'];
    }
    $keys = explode('.', $key);
    $value = $GLOBALS['config'];
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    return $value;
}

// Script start
echo "=== CREODENT Evolution Import Job ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

$startTime = microtime(true);
$jobId = null;

try {
    // Check if Evolution is enabled
    if (!config('evolution.enabled')) {
        echo "Evolution integration is disabled in config. Exiting.\n";
        exit(0);
    }

    // Initialize database
    $pdo = Database::getConnection();

    // Create import job record
    $stmt = $pdo->prepare("
        INSERT INTO import_jobs (job_type, started_at, status, records_processed, records_failed)
        VALUES ('evo_case_list', NOW(), 'success', 0, 0)
    ");
    $stmt->execute();
    $jobId = $pdo->lastInsertId();

    echo "Job ID: $jobId\n";

    // Initialize services
    $evoClient = new EvolutionClient();
    $caseService = new CaseService();

    // Test connection first
    echo "Testing Evolution connection...\n";
    if (!$evoClient->testConnection()) {
        throw new Exception('Failed to connect to Evolution Web Portal');
    }
    echo "✓ Connection successful\n\n";

    // Determine date range
    // IMPORTANT: Always use date ranges to prevent infinite data loading
    $daysBack = config('cron.import_days_back', 7);
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$daysBack} days"));

    echo "Importing cases from $startDate to $endDate\n";
    echo "Date range: $daysBack days\n\n";

    // Get case list from Evolution
    echo "Fetching case list from Evolution...\n";
    $caseListResponse = $evoClient->getCaseList($startDate, $endDate);

    if (!isset($caseListResponse['cases'])) {
        echo "No 'cases' key in response. Checking for alternative structure...\n";
        // Handle different response structures
        if (isset($caseListResponse['case'])) {
            $cases = is_array($caseListResponse['case']) ? $caseListResponse['case'] : [$caseListResponse['case']];
        } else {
            $cases = [];
        }
    } else {
        $cases = $caseListResponse['cases'];
        if (!is_array($cases)) {
            $cases = [$cases];
        }
    }

    $totalCases = count($cases);
    echo "Found $totalCases case(s)\n\n";

    if ($totalCases === 0) {
        echo "No cases to import.\n";

        // Update job record
        $stmt = $pdo->prepare("
            UPDATE import_jobs
            SET ended_at = NOW(), message = 'No cases found in date range'
            WHERE id = :job_id
        ");
        $stmt->execute(['job_id' => $jobId]);

        exit(0);
    }

    // Import each case
    $imported = 0;
    $failed = 0;
    $batchSize = config('cron.import_batch_size', 50);

    foreach ($cases as $index => $caseData) {
        $caseNumber = $caseData['casenumber'] ?? $caseData['case_number'] ?? 'UNKNOWN-' . ($index + 1);

        echo "[$index + 1/$totalCases] Processing case: $caseNumber\n";

        try {
            // Get detailed information for this case
            echo "  Fetching case details...\n";
            $caseDetails = $evoClient->getCaseInformation($caseNumber);

            // Merge list data with detail data
            $fullCaseData = array_merge($caseData, $caseDetails);

            // Import to database
            echo "  Importing to database...\n";
            $result = $caseService->importFromEvolution($fullCaseData);

            if ($result['success']) {
                $imported++;
                echo "  ✓ Success (Case ID: {$result['case_id']})\n";
            } else {
                $failed++;
                echo "  ✗ Failed: {$result['error']}\n";
            }

        } catch (Exception $e) {
            $failed++;
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }

        echo "\n";

        // Rate limiting: small delay between requests
        if (($index + 1) % $batchSize === 0) {
            echo "  Batch complete, pausing 2 seconds...\n\n";
            sleep(2);
        }
    }

    // Summary
    echo "=== Import Complete ===\n";
    echo "Total cases: $totalCases\n";
    echo "Imported: $imported\n";
    echo "Failed: $failed\n";

    $duration = round(microtime(true) - $startTime, 2);
    echo "Duration: {$duration}s\n";

    // Update job record
    $stmt = $pdo->prepare("
        UPDATE import_jobs
        SET ended_at = NOW(),
            status = :status,
            records_processed = :imported,
            records_failed = :failed,
            message = :message
        WHERE id = :job_id
    ");

    $status = $failed > 0 ? ($imported > 0 ? 'partial' : 'error') : 'success';
    $message = "Imported $imported out of $totalCases cases in {$duration}s";

    $stmt->execute([
        'job_id' => $jobId,
        'status' => $status,
        'imported' => $imported,
        'failed' => $failed,
        'message' => $message,
    ]);

    echo "\nJob ID $jobId completed with status: $status\n";

} catch (Exception $e) {
    echo "\n=== FATAL ERROR ===\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";

    // Update job record as error
    if ($jobId) {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                UPDATE import_jobs
                SET ended_at = NOW(),
                    status = 'error',
                    message = :message
                WHERE id = :job_id
            ");
            $stmt->execute([
                'job_id' => $jobId,
                'message' => $e->getMessage(),
            ]);
        } catch (Exception $updateError) {
            echo "Failed to update job record: " . $updateError->getMessage() . "\n";
        }
    }

    exit(1);
}

echo "\nFinished at: " . date('Y-m-d H:i:s') . "\n";
exit(0);
