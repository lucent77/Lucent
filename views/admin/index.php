<?php $pageTitle = 'Admin - CREODENT'; ?>
<?php require VIEWS_PATH . '/layouts/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Page header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Administration
        </h2>
        <p class="mt-1 text-sm text-gray-500">
            System management and configuration
        </p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Users</dt>
                            <dd class="text-3xl font-semibold text-gray-900"><?= e($data['stats']['total_users']) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="/admin/users" class="font-medium text-blue-600 hover:text-blue-500">Manage users</a>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Cases</dt>
                            <dd class="text-3xl font-semibold text-gray-900"><?= e($data['stats']['total_cases']) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="/cases" class="font-medium text-blue-600 hover:text-blue-500">View cases</a>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Departments</dt>
                            <dd class="text-3xl font-semibold text-gray-900"><?= e($data['stats']['total_departments']) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="/admin/departments" class="font-medium text-blue-600 hover:text-blue-500">Manage departments</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <a href="/admin/users" class="border border-gray-300 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition">
                    <h4 class="text-sm font-medium text-gray-900">User Management</h4>
                    <p class="mt-1 text-sm text-gray-500">Create, edit, and manage user accounts</p>
                </a>

                <a href="/admin/departments" class="border border-gray-300 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition">
                    <h4 class="text-sm font-medium text-gray-900">Department Management</h4>
                    <p class="mt-1 text-sm text-gray-500">Configure departments and work types</p>
                </a>

                <a href="/admin/imports" class="border border-gray-300 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition">
                    <h4 class="text-sm font-medium text-gray-900">Import Monitor</h4>
                    <p class="mt-1 text-sm text-gray-500">View Evolution import jobs and run manual imports</p>
                </a>

                <div class="border border-gray-300 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Evolution Connection Test</h4>
                    <button onclick="testEvolution()" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Test Connection
                    </button>
                    <p id="test-result" class="mt-2 text-sm text-gray-500"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- System information -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">System Information</h3>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Application Name</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?= e(config('app.name')) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Environment</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?= e(config('app.env')) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Database</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?= e(config('database.database')) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Evolution Integration</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?= config('evolution.enabled') ? '<span class="text-green-600">Enabled</span>' : '<span class="text-gray-500">Disabled</span>' ?>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<script>
async function testEvolution() {
    const button = event.target;
    const resultEl = document.getElementById('test-result');

    button.disabled = true;
    button.textContent = 'Testing...';
    resultEl.textContent = '';

    try {
        const response = await fetch('/admin/test-evolution', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            }
        });

        const result = await response.json();

        if (result.success) {
            resultEl.className = 'mt-2 text-sm text-green-600';
            resultEl.textContent = '✓ ' + result.message;
        } else {
            resultEl.className = 'mt-2 text-sm text-red-600';
            resultEl.textContent = '✗ ' + (result.error || result.message);
        }
    } catch (error) {
        resultEl.className = 'mt-2 text-sm text-red-600';
        resultEl.textContent = '✗ Network error: ' + error.message;
    }

    button.disabled = false;
    button.textContent = 'Test Connection';
}
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
