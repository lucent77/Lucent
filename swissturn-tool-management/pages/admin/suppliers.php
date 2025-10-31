<?php
define('SYSTEM_INIT', true);
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../auth/session.php';
requireAdmin();
$page_title = 'Suppliers';
include __DIR__ . '/../../components/header.php';
include __DIR__ . '/../../components/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white shadow-sm">
        <div class="px-6 py-4"><h1 class="text-2xl font-bold text-gray-800">Suppliers</h1></div>
    </header>
    <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600">Suppliers management - API endpoints ready, UI to be implemented</p>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../../components/footer.php'; ?>
