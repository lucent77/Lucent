<?php
/**
 * Swissturn Tool Management System
 * Sidebar Navigation Component
 */

if (!defined('SYSTEM_INIT')) {
    die('Direct access not permitted');
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$is_admin = hasRole(ROLE_ADMIN);
?>

<!-- Sidebar -->
<aside class="w-64 bg-blue-900 text-white flex-shrink-0 hidden md:block overflow-y-auto">
    <div class="p-6">
        <!-- Logo -->
        <div class="flex items-center space-x-3 mb-8">
            <div class="bg-blue-700 rounded-lg p-2">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold">Swissturn</h1>
                <p class="text-xs text-blue-300">Tool Management</p>
            </div>
        </div>

        <!-- User Info -->
        <div class="bg-blue-800 rounded-lg p-4 mb-6">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-600 rounded-full p-2">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate"><?php echo e($_SESSION['full_name']); ?></p>
                    <p class="text-xs text-blue-300 capitalize"><?php echo e($_SESSION['role']); ?></p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="space-y-1">
            <?php if ($is_admin): ?>
                <!-- Admin Navigation -->
                <a href="<?php echo BASE_URL; ?>/pages/admin/dashboard.php"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $current_page === 'dashboard' ? 'bg-blue-700' : 'hover:bg-blue-800'; ?> transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/pages/admin/tools.php"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $current_page === 'tools' ? 'bg-blue-700' : 'hover:bg-blue-800'; ?> transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                    </svg>
                    <span>Tools</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/pages/admin/orders.php"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $current_page === 'orders' ? 'bg-blue-700' : 'hover:bg-blue-800'; ?> transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Purchase Orders</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/pages/admin/suppliers.php"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $current_page === 'suppliers' ? 'bg-blue-700' : 'hover:bg-blue-800'; ?> transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Suppliers</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/pages/admin/users.php"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $current_page === 'users' ? 'bg-blue-700' : 'hover:bg-blue-800'; ?> transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Users</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/pages/admin/reports.php"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $current_page === 'reports' ? 'bg-blue-700' : 'hover:bg-blue-800'; ?> transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Reports</span>
                </a>

            <?php else: ?>
                <!-- Worker Navigation -->
                <a href="<?php echo BASE_URL; ?>/pages/worker/dashboard.php"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $current_page === 'dashboard' ? 'bg-blue-700' : 'hover:bg-blue-800'; ?> transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/pages/worker/checkout.php"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $current_page === 'checkout' ? 'bg-blue-700' : 'hover:bg-blue-800'; ?> transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2h2m3-4H9a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-1m-1 4l-3 3m0 0l-3-3m3 3V3"/>
                    </svg>
                    <span>Check Out/In</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/pages/worker/history.php"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $current_page === 'history' ? 'bg-blue-700' : 'hover:bg-blue-800'; ?> transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>My History</span>
                </a>
            <?php endif; ?>

            <!-- Logout -->
            <div class="pt-4 mt-4 border-t border-blue-800">
                <a href="<?php echo BASE_URL; ?>/auth/logout.php"
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-red-700 transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </div>
</aside>
