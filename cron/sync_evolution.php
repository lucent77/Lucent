<?php
/**
 * Cron Job: Sync Recent Cases from Evolution Portal
 *
 * Usage: php cron/sync_evolution.php [days_back]
 * Example: php cron/sync_evolution.php 7
 *
 * Setup cron (every hour):
 * 0 * * * * php /path/to/project/cron/sync_evolution.php >> /path/to/project/logs/cron.log 2>&1
 */

// Set working directory to project root
chdir(dirname(__DIR__));

// Autoloader
require_once __DIR__ . '/../public/index.php';

use App\Services\ImportService;
use App\Core\Logger;

// Get configuration
$config = require __DIR__ . '/../config/config.php';

// Initialize logger
$logger = new Logger($config['logging']['path'], 'cron');

$logger->info('=== Evolution Sync Cron Job Started ===');

try {
    // Check if sync is enabled
    if (!$config['import']['auto_sync_enabled']) {
        $logger->info('Auto sync is disabled in configuration');
        exit(0);
    }

    // Get days back from command line argument or config
    $daysBack = isset($argv[1]) ? (int)$argv[1] : ($config['import']['sync_date_range'] ?? 7);

    $logger->info("Syncing cases from last $daysBack days");

    // Initialize import service
    $importService = new ImportService($config);

    // Test connection first
    $logger->info('Testing Evolution Portal connection...');
    $connectionTest = $importService->testEvolutionConnection();

    if (!$connectionTest['success']) {
        throw new \RuntimeException('Connection test failed: ' . ($connectionTest['message'] ?? 'Unknown error'));
    }

    $logger->info('Connection test successful');

    // Perform sync
    $result = $importService->syncRecentCases($daysBack);

    if ($result['success']) {
        $logger->info("Sync completed successfully", [
            'processed' => $result['processed'],
            'failed' => $result['failed']
        ]);

        if (!empty($result['errors'])) {
            $logger->warning('Some cases failed to import', [
                'errors' => $result['errors']
            ]);
        }

        echo "SUCCESS: Processed {$result['processed']} cases, {$result['failed']} failed\n";
        exit(0);

    } else {
        throw new \RuntimeException('Sync failed: ' . ($result['error'] ?? 'Unknown error'));
    }

} catch (\Exception $e) {
    $logger->error('Cron job failed: ' . $e->getMessage(), [
        'exception' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);

} finally {
    $logger->info('=== Evolution Sync Cron Job Ended ===');
}
