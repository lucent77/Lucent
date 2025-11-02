<?php $pageTitle = 'Import Monitor - CREODENT'; ?>
<?php require VIEWS_PATH . '/layouts/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold text-gray-900">Import Monitor</h2>
            <p class="mt-1 text-sm text-gray-500">View and manage Evolution import jobs</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <button onclick="triggerImport()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Run Import Now
            </button>
        </div>
    </div>

    <!-- Imports table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ended</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Records</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($data['imports'])): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            No import jobs found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['imports'] as $import): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= e($import['id']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= e($import['job_type']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= e($import['started_at']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= e($import['ended_at'] ?? 'Running...') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $import['status'] === 'success' ? 'green' : ($import['status'] === 'partial' ? 'yellow' : 'red') ?>-100 text-<?= $import['status'] === 'success' ? 'green' : ($import['status'] === 'partial' ? 'yellow' : 'red') ?>-800">
                                    <?= e($import['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= e($import['records_processed'] ?? 0) ?> / <?= e(($import['records_processed'] ?? 0) + ($import['records_failed'] ?? 0)) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                <?= e($import['message'] ?? '') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>Note:</strong> Import jobs are automatically run by the cron script.
                    The "Run Import Now" button triggers a manual import for testing purposes.
                    Check the cron/import_evo.php file for more details.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
async function triggerImport() {
    if (!confirm('This will start a manual Evolution import. Continue?')) {
        return;
    }

    const button = event.target;
    button.disabled = true;
    button.textContent = 'Importing...';

    try {
        const response = await fetch('/admin/trigger-import', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            }
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Import started successfully. Refresh the page to see results.', 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showNotification(result.error || 'Import failed', 'error');
            button.disabled = false;
            button.textContent = 'Run Import Now';
        }
    } catch (error) {
        showNotification('Network error: ' + error.message, 'error');
        button.disabled = false;
        button.textContent = 'Run Import Now';
    }
}
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
