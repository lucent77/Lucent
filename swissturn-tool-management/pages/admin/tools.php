<?php
/**
 * Swissturn Tool Management System
 * Admin Tools Management Page
 */

define('SYSTEM_INIT', true);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../auth/session.php';

requireAdmin();

$page_title = 'Tools Management';
$page_scripts = ['tools'];

include __DIR__ . '/../../components/header.php';
include __DIR__ . '/../../components/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white shadow-sm">
        <div class="px-6 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Tools Management</h1>
                <p class="text-sm text-gray-600">Manage tool inventory and lifecycle</p>
            </div>
            <button onclick="showAddToolModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                + Add Tool
            </button>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
        <?php include __DIR__ . '/../../components/alerts.php'; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" id="search-input" placeholder="Search tools..." class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <select id="status-filter" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="near_expiry">Near Expiry</option>
                    <option value="expired">Expired</option>
                    <option value="needs_reorder">Needs Reorder</option>
                </select>
                <select id="category-filter" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                </select>
                <button onclick="loadTools()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                    Apply Filters
                </button>
            </div>
        </div>

        <!-- Tools Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tool Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lifecycle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="tools-table-body" class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
async function loadTools() {
    try {
        let url = '<?php echo API_URL; ?>/tools.php';
        const params = new URLSearchParams();

        const status = document.getElementById('status-filter').value;
        if (status) params.append('status', status);

        const category = document.getElementById('category-filter').value;
        if (category) params.append('category_id', category);

        const search = document.getElementById('search-input').value;
        if (search) params.append('search', search);

        if (params.toString()) url += '?' + params.toString();

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            displayTools(result.data);
        }
    } catch (error) {
        console.error('Error loading tools:', error);
    }
}

function displayTools(tools) {
    const tbody = document.getElementById('tools-table-body');

    if (tools.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No tools found</td></tr>';
        return;
    }

    tbody.innerHTML = tools.map(tool => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${tool.tool_name}</div>
                <div class="text-sm text-gray-500">${tool.tool_size || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${tool.category_name || '-'}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${tool.current_stock} / ${tool.minimum_stock}</div>
                ${tool.current_stock <= tool.minimum_stock ? '<span class="text-xs text-red-600">Low Stock</span>' : ''}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">${getStatusBadge(tool.status)}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: ${tool.lifecycle_percentage}%"></div>
                </div>
                <span class="text-xs text-gray-500">${tool.lifecycle_percentage.toFixed(0)}%</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button onclick="editTool(${tool.tool_id})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                <button onclick="deleteTool(${tool.tool_id})" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
        </tr>
    `).join('');
}

function getStatusBadge(status) {
    const badges = {
        'active': '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>',
        'near_expiry': '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Near Expiry</span>',
        'expired': '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Expired</span>',
        'needs_reorder': '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Needs Reorder</span>'
    };
    return badges[status] || status;
}

function showAddToolModal() {
    alert('Add Tool Modal - To be implemented');
}

function editTool(id) {
    alert('Edit Tool #' + id + ' - To be implemented');
}

async function deleteTool(id) {
    if (!confirm('Are you sure you want to delete this tool?')) return;

    try {
        const response = await fetch('<?php echo API_URL; ?>/tools.php?id=' + id, {
            method: 'DELETE'
        });
        const result = await response.json();

        if (result.success) {
            alert('Tool deleted successfully');
            loadTools();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting tool:', error);
    }
}

document.addEventListener('DOMContentLoaded', loadTools);
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
