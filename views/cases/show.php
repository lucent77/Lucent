<?php $pageTitle = 'Case ' . e($data['case']['external_case_no']) . ' - CREODENT'; ?>
<?php require VIEWS_PATH . '/layouts/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="caseDetail(<?= htmlspecialchars(json_encode($data['case'])) ?>)">
    <!-- Page header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Case: <?= e($data['case']['external_case_no']) ?>
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Created <?= e(date('Y-m-d H:i', strtotime($data['case']['created_at']))) ?>
                • Version <?= e($data['case']['version']) ?>
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="/cases" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Back to Cases
            </a>
        </div>
    </div>

    <!-- Master case information -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Case Information</h3>
            <form @submit.prevent="saveMasterInfo">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Case Number</label>
                        <input type="text" x-model="caseData.external_case_no" disabled
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select x-model="caseData.status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="new">New</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Done</option>
                            <option value="on_hold">On Hold</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lab Name</label>
                        <input type="text" x-model="caseData.lab_name"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Patient Name</label>
                        <input type="text" x-model="caseData.patient_name"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <input type="text" x-model="caseData.location"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Due Date</label>
                        <input type="date" x-model="caseData.due_date"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Save Master Info
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabbed work sections (SOLIDEX, 3D PRINT, CoCr) -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button @click="activeTab = 'solidex'"
                        :class="activeTab === 'solidex' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    SOLIDEX
                </button>
                <button @click="activeTab = '3dprint'"
                        :class="activeTab === '3dprint' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    3D PRINT
                </button>
                <button @click="activeTab = 'cocr'"
                        :class="activeTab === 'cocr' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    ZEST / CoCr
                </button>
            </nav>
        </div>

        <!-- SOLIDEX Tab -->
        <div x-show="activeTab === 'solidex'" x-cloak class="px-6 py-4">
            <h4 class="text-md font-semibold mb-4">SOLIDEX Work Items</h4>
            <template x-for="item in getItemsByType('SOLIDEX')" :key="item.id">
                <div class="border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tooth #</label>
                            <input type="text" x-model="item.tooth_no"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Count</label>
                            <input type="number" x-model="item.count"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select x-model="item.status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="pending">Pending</option>
                                <option value="assigned">Assigned</option>
                                <option value="working">Working</option>
                                <option value="done">Done</option>
                                <option value="remake">Remake</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Instructions</label>
                            <textarea x-model="item.instruction" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                        </div>
                        <div class="sm:col-span-3">
                            <button @click="saveItem(item)"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Save SOLIDEX Item
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            <div x-show="getItemsByType('SOLIDEX').length === 0" class="text-center py-6 text-gray-500">
                No SOLIDEX items for this case
            </div>
        </div>

        <!-- 3D PRINT Tab -->
        <div x-show="activeTab === '3dprint'" x-cloak class="px-6 py-4">
            <h4 class="text-md font-semibold mb-4">3D PRINT Work Items</h4>
            <template x-for="item in getItemsByType('PRINT3D')" :key="item.id">
                <div class="border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tooth #</label>
                            <input type="text" x-model="item.tooth_no"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Count</label>
                            <input type="number" x-model="item.count"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select x-model="item.status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="pending">Pending</option>
                                <option value="assigned">Assigned</option>
                                <option value="working">Working</option>
                                <option value="done">Done</option>
                                <option value="remake">Remake</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Instructions</label>
                            <textarea x-model="item.instruction" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Preferences (e.g., uploaded Korea)</label>
                            <input type="text" x-model="item.preferences"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div class="sm:col-span-3">
                            <button @click="saveItem(item)"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Save 3D PRINT Item
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            <div x-show="getItemsByType('PRINT3D').length === 0" class="text-center py-6 text-gray-500">
                No 3D PRINT items for this case
            </div>
        </div>

        <!-- CoCr/Zest Tab -->
        <div x-show="activeTab === 'cocr'" x-cloak class="px-6 py-4">
            <h4 class="text-md font-semibold mb-4">ZEST / CoCr Work Items</h4>
            <template x-for="item in getItemsByType('COCR')" :key="item.id">
                <div class="border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tooth #</label>
                            <input type="text" x-model="item.tooth_no"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Count</label>
                            <input type="number" x-model="item.count"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select x-model="item.status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="pending">Pending</option>
                                <option value="assigned">Assigned</option>
                                <option value="working">Working</option>
                                <option value="done">Done</option>
                                <option value="remake">Remake</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Instructions</label>
                            <textarea x-model="item.instruction" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Type/Preferences</label>
                            <input type="text" x-model="item.preferences"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div class="sm:col-span-3">
                            <button @click="saveItem(item)"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Save CoCr Item
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            <div x-show="getItemsByType('COCR').length === 0" class="text-center py-6 text-gray-500">
                No ZEST/CoCr items for this case
            </div>
        </div>
    </div>

    <!-- Audit log -->
    <?php if (!empty($data['case']['audit_logs'])): ?>
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Activity Log</h3>
                <div class="flow-root">
                    <ul class="-mb-8">
                        <?php foreach ($data['case']['audit_logs'] as $index => $log): ?>
                            <li>
                                <div class="relative pb-8">
                                    <?php if ($index < count($data['case']['audit_logs']) - 1): ?>
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm text-gray-500">
                                                    <?= e($log['description'] ?? $log['action']) ?>
                                                    <?php if ($log['user_name']): ?>
                                                        by <span class="font-medium text-gray-900"><?= e($log['user_name']) ?></span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                <?= e(date('Y-m-d H:i', strtotime($log['created_at']))) ?>
                                            </div>
                                        </div>
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

<script>
function caseDetail(initialData) {
    return {
        caseData: { ...initialData },
        activeTab: 'solidex',

        getItemsByType(type) {
            return this.caseData.items.filter(item => item.work_type === type);
        },

        async saveMasterInfo() {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('version', this.caseData.version);
            formData.append('patient_name', this.caseData.patient_name || '');
            formData.append('lab_name', this.caseData.lab_name || '');
            formData.append('location', this.caseData.location || '');
            formData.append('due_date', this.caseData.due_date || '');
            formData.append('status', this.caseData.status);

            try {
                const response = await fetch(`/cases/${this.caseData.id}/update`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Case updated successfully', 'success');
                    this.caseData.version++;
                } else if (result.error === 'CONFLICT') {
                    handleVersionConflict();
                } else {
                    showNotification(result.error || 'Update failed', 'error');
                }
            } catch (error) {
                showNotification('Network error: ' + error.message, 'error');
            }
        },

        async saveItem(item) {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('version', item.version);
            formData.append('tooth_no', item.tooth_no || '');
            formData.append('count', item.count);
            formData.append('instruction', item.instruction || '');
            formData.append('preferences', item.preferences || '');
            formData.append('status', item.status);

            try {
                const response = await fetch(`/cases/item/${item.id}/update`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Item updated successfully', 'success');
                    item.version++;
                } else if (result.error === 'CONFLICT') {
                    handleVersionConflict();
                } else {
                    showNotification(result.error || 'Update failed', 'error');
                }
            } catch (error) {
                showNotification('Network error: ' + error.message, 'error');
            }
        }
    };
}
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
