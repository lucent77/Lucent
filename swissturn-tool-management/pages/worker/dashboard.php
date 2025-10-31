<?php
define('SYSTEM_INIT', true);
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../auth/session.php';
requireLogin();
$page_title = 'Worker Dashboard';
include __DIR__ . '/../../components/header.php';
include __DIR__ . '/../../components/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white shadow-sm">
        <div class="px-6 py-4">
            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-sm text-gray-600">Welcome back, <?php echo e($_SESSION['full_name']); ?>!</p>
        </div>
    </header>
    <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="<?php echo BASE_URL; ?>/pages/worker/checkout.php" class="block bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition text-center">Check Out Tool</a>
                    <a href="<?php echo BASE_URL; ?>/pages/worker/checkout.php" class="block bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition text-center">Check In Tool</a>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">My Stats</h3>
                <p class="text-gray-600">View your transaction history</p>
                <a href="<?php echo BASE_URL; ?>/pages/worker/history.php" class="text-blue-600 hover:text-blue-800">View History →</a>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../../components/footer.php'; ?>
