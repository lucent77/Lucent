<?php $pageTitle = 'Cases - CREODENT'; ?>
<?php require VIEWS_PATH . '/layouts/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Page header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Cases
            </h2>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <?php if (hasAnyRole(['super_admin', 'admin', 'manager'])): ?>
                <a href="/cases/create" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New Case
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg mb-6 p-4">
        <form method="GET" action="/cases" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="case_number" class="block text-sm font-medium text-gray-700">Case Number</label>
                <input type="text" name="case_number" id="case_number"
                       value="<?= e($data['filters']['case_number'] ?? '') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                       placeholder="Search...">
            </div>

            <div>
                <label for="patient_name" class="block text-sm font-medium text-gray-700">Patient Name</label>
                <input type="text" name="patient_name" id="patient_name"
                       value="<?= e($data['filters']['patient_name'] ?? '') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                       placeholder="Search...">
            </div>

            <div>
                <label for="lab_name" class="block text-sm font-medium text-gray-700">Lab Name</label>
                <input type="text" name="lab_name" id="lab_name"
                       value="<?= e($data['filters']['lab_name'] ?? '') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                       placeholder="Search...">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">All</option>
                    <option value="new" <?= ($data['filters']['status'] ?? '') === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="in_progress" <?= ($data['filters']['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="done" <?= ($data['filters']['status'] ?? '') === 'done' ? 'selected' : '' ?>>Done</option>
                    <option value="on_hold" <?= ($data['filters']['status'] ?? '') === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                    <option value="canceled" <?= ($data['filters']['status'] ?? '') === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>

            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                <input type="date" name="date_from" id="date_from"
                       value="<?= e($data['filters']['date_from'] ?? '') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                <input type="date" name="date_to" id="date_to"
                       value="<?= e($data['filters']['date_to'] ?? '') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit"
                        class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Filter
                </button>
                <a href="/cases"
                   class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Cases table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Case Number
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Lab / Patient
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Source
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Items
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Updated
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($data['cases'])): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            No cases found. Try adjusting your filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['cases'] as $case): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-blue-600">
                                    <a href="/cases/<?= $case['id'] ?>" class="hover:text-blue-500">
                                        <?= e($case['external_case_no']) ?>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= e($case['lab_name'] ?? 'N/A') ?></div>
                                <div class="text-sm text-gray-500"><?= e($case['patient_name'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    <?= e(strtoupper($case['source'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $case['status'] === 'done' ? 'green' : ($case['status'] === 'in_progress' ? 'yellow' : 'blue') ?>-100 text-<?= $case['status'] === 'done' ? 'green' : ($case['status'] === 'in_progress' ? 'yellow' : 'blue') ?>-800">
                                    <?= e(ucfirst(str_replace('_', ' ', $case['status']))) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= e($case['completed_items']) ?> / <?= e($case['items_count']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= e(date('Y-m-d H:i', strtotime($case['updated_at']))) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/cases/<?= $case['id'] ?>" class="text-blue-600 hover:text-blue-900">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($data['pagination']['pages'] > 1): ?>
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4 rounded-lg shadow">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($data['pagination']['current_page'] > 1): ?>
                    <a href="?page=<?= $data['pagination']['current_page'] - 1 ?>&<?= http_build_query($data['filters']) ?>"
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                <?php endif; ?>
                <?php if ($data['pagination']['current_page'] < $data['pagination']['pages']): ?>
                    <a href="?page=<?= $data['pagination']['current_page'] + 1 ?>&<?= http_build_query($data['filters']) ?>"
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                <?php endif; ?>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing
                        <span class="font-medium"><?= ($data['pagination']['current_page'] - 1) * $data['pagination']['per_page'] + 1 ?></span>
                        to
                        <span class="font-medium"><?= min($data['pagination']['current_page'] * $data['pagination']['per_page'], $data['pagination']['total']) ?></span>
                        of
                        <span class="font-medium"><?= $data['pagination']['total'] ?></span>
                        results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php for ($i = 1; $i <= $data['pagination']['pages']; $i++): ?>
                            <a href="?page=<?= $i ?>&<?= http_build_query($data['filters']) ?>"
                               class="<?= $i === $data['pagination']['current_page'] ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
