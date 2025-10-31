<?php
/**
 * Swissturn Tool Management System
 * Admin Dashboard
 */

define('SYSTEM_INIT', true);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../auth/session.php';

// Require admin access
requireAdmin();

$page_title = 'Admin Dashboard';
$page_scripts = ['charts'];

// Include header and sidebar
include __DIR__ . '/../../components/header.php';
include __DIR__ . '/../../components/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 flex flex-col overflow-hidden">
    <!-- Top Bar -->
    <header class="bg-white shadow-sm">
        <div class="px-6 py-4">
            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-sm text-gray-600">Welcome back, <?php echo e($_SESSION['full_name']); ?>!</p>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
        <?php include __DIR__ . '/../../components/alerts.php'; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Tools -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Tools</p>
                        <p id="stat-total-tools" class="text-3xl font-bold text-gray-800">-</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Low Stock -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Low Stock</p>
                        <p id="stat-low-stock" class="text-3xl font-bold text-orange-600">-</p>
                    </div>
                    <div class="bg-orange-100 rounded-full p-3">
                        <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Near Expiry -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Near Expiry</p>
                        <p id="stat-near-expiry" class="text-3xl font-bold text-yellow-600">-</p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Orders -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Pending Orders</p>
                        <p id="stat-pending-orders" class="text-3xl font-bold text-blue-600">-</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Usage Trends Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Tool Usage Trends (Last 30 Days)</h3>
                <div class="h-64">
                    <canvas id="usageChart"></canvas>
                </div>
            </div>

            <!-- Category Distribution Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Tools by Category</h3>
                <div class="h-64">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Transactions</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tool</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Job ID</th>
                        </tr>
                    </thead>
                    <tbody id="recent-transactions" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
// Load dashboard data
async function loadDashboardData() {
    try {
        const response = await fetch('<?php echo API_URL; ?>/reports.php?type=dashboard');
        const result = await response.json();

        if (result.success) {
            const data = result.data;

            // Update statistics
            document.getElementById('stat-total-tools').textContent = data.total_tools;
            document.getElementById('stat-low-stock').textContent = data.low_stock_tools;
            document.getElementById('stat-near-expiry').textContent = data.tools_near_expiry;
            document.getElementById('stat-pending-orders').textContent = data.pending_orders;

            // Update recent transactions
            displayRecentTransactions(data.recent_transactions);

            // Create charts
            createUsageChart();
            createCategoryChart(data.category_distribution);
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

function displayRecentTransactions(transactions) {
    const tbody = document.getElementById('recent-transactions');

    if (transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No recent transactions</td></tr>';
        return;
    }

    tbody.innerHTML = transactions.map(t => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatDateTime(t.transaction_date)}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded ${t.transaction_type === 'checkout' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                    ${t.transaction_type}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${t.tool_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${t.user_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${t.job_id || '-'}</td>
        </tr>
    `).join('');
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

async function createUsageChart() {
    try {
        const response = await fetch('<?php echo API_URL; ?>/reports.php?type=usage&days=30');
        const result = await response.json();

        if (result.success) {
            const ctx = document.getElementById('usageChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: result.data.map(d => d.date),
                    datasets: [{
                        label: 'Checkouts',
                        data: result.data.map(d => d.checkouts),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Check-ins',
                        data: result.data.map(d => d.checkins),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error creating usage chart:', error);
    }
}

function createCategoryChart(categories) {
    if (!categories || categories.length === 0) return;

    const ctx = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: categories.map(c => c.category_name),
            datasets: [{
                data: categories.map(c => c.tool_count),
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(34, 197, 94)',
                    'rgb(251, 191, 36)',
                    'rgb(239, 68, 68)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
}

// Load data on page load
document.addEventListener('DOMContentLoaded', loadDashboardData);
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
