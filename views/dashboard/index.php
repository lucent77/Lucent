<?php $pageTitle = 'Dashboard - CREODENT'; ?>
<?php require VIEWS_PATH . '/layouts/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Page header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Dashboard
            </h2>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <!-- Date range selector -->
            <form method="GET" action="/dashboard" class="flex items-center space-x-2">
                <label for="days" class="text-sm text-gray-700">Show last:</label>
                <select name="days" id="days" onchange="this.form.submit()"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="7" <?= $data['date_range']['days'] === 7 ? 'selected' : '' ?>>7 days</option>
                    <option value="30" <?= $data['date_range']['days'] === 30 ? 'selected' : '' ?>>30 days</option>
                    <option value="90" <?= $data['date_range']['days'] === 90 ? 'selected' : '' ?>>90 days</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        <!-- Total cases by status -->
        <?php
        $statusLabels = ['new' => 'New Cases', 'in_progress' => 'In Progress', 'done' => 'Completed'];
        $statusColors = ['new' => 'blue', 'in_progress' => 'yellow', 'done' => 'green'];

        foreach ($data['stats']['status_counts'] as $stat):
            $status = $stat['status'];
            $label = $statusLabels[$status] ?? ucfirst($status);
            $color = $statusColors[$status] ?? 'gray';
        ?>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-<?= $color ?>-500 p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    <?= e($label) ?>
                                </dt>
                                <dd class="text-3xl font-semibold text-gray-900">
                                    <?= e($stat['count']) ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Department statistics -->
    <?php if (!empty($data['stats']['department_counts'])): ?>
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Cases by Department
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($data['stats']['department_counts'] as $dept): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500"><?= e($dept['name']) ?></div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900"><?= e($dept['case_count']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Charts -->
    <?php if (!empty($data['stats']['daily_counts'])): ?>
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Cases per Day
                    <span class="text-sm font-normal text-gray-500">
                        (<?= e($data['date_range']['from']) ?> to <?= e($data['date_range']['to']) ?>)
                    </span>
                </h3>
                <div class="h-64 flex items-end space-x-2">
                    <?php
                    $maxCount = max(array_column($data['stats']['daily_counts'], 'count'));
                    foreach (array_reverse($data['stats']['daily_counts']) as $day):
                        $height = $maxCount > 0 ? ($day['count'] / $maxCount * 100) : 0;
                    ?>
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-blue-500 rounded-t" style="height: <?= $height ?>%"
                                 title="<?= e($day['date']) ?>: <?= e($day['count']) ?> cases"></div>
                            <div class="text-xs text-gray-500 mt-1 transform -rotate-45 origin-top-left">
                                <?= e(date('m/d', strtotime($day['date']))) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent cases and my items -->
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <!-- Recent cases -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Recent Cases
                </h3>
                <div class="flow-root">
                    <ul class="-my-5 divide-y divide-gray-200">
                        <?php foreach ($data['recent_cases'] as $case): ?>
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1 min-w-0">
                                        <a href="/cases/<?= $case['id'] ?>" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                            <?= e($case['external_case_no']) ?>
                                        </a>
                                        <p class="text-sm text-gray-500 truncate">
                                            <?= e($case['lab_name'] ?? 'N/A') ?> - <?= e($case['patient_name'] ?? 'N/A') ?>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $case['status'] === 'done' ? 'green' : 'blue' ?>-100 text-<?= $case['status'] === 'done' ? 'green' : 'blue' ?>-800">
                                            <?= e(ucfirst($case['status'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="mt-6">
                    <a href="/cases" class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        View all cases
                    </a>
                </div>
            </div>
        </div>

        <!-- My items (for workers/managers) -->
        <?php if (!empty($data['my_items'])): ?>
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        My Assigned Items
                    </h3>
                    <div class="flow-root">
                        <ul class="-my-5 divide-y divide-gray-200">
                            <?php foreach ($data['my_items'] as $item): ?>
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-1 min-w-0">
                                            <a href="/cases/<?= $item['case_id'] ?>" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                                <?= e($item['external_case_no']) ?>
                                            </a>
                                            <p class="text-sm text-gray-500">
                                                <?= e($item['department_name']) ?> - <?= e($item['work_type']) ?>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <?= e(ucfirst($item['status'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent imports -->
    <?php if (!empty($data['recent_imports'])): ?>
        <div class="bg-white shadow rounded-lg mt-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Recent Imports
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Started
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Records
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($data['recent_imports'] as $import): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= e($import['job_type']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= e($import['started_at']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $import['status'] === 'success' ? 'green' : 'red' ?>-100 text-<?= $import['status'] === 'success' ? 'green' : 'red' ?>-800">
                                            <?= e($import['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= e($import['records_processed'] ?? 0) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
