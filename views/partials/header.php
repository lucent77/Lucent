<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'CREODENT CADCAM Work Manager' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        /* Prevent infinite chart height */
        .chart-container {
            position: relative;
            height: 300px !important;
            max-height: 300px !important;
            width: 100%;
        }
        .chart-container canvas {
            max-height: 300px !important;
        }
    </style>
    <script>
        // Global config
        window.APP_CONFIG = {
            csrf_token: '<?= $auth->generateCsrfToken() ?>',
            user: <?= $auth->check() ? json_encode($auth->user()) : 'null' ?>
        };
    </script>
</head>
<body class="bg-gray-50">
    <?php if ($auth->check()): ?>
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold text-blue-600">CREODENT</span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/dashboard" class="border-transparent text-gray-900 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <?php if ($auth->can('cases.view')): ?>
                        <a href="/cases" class="border-transparent text-gray-900 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Cases
                        </a>
                        <?php endif; ?>
                        <?php if ($auth->isAdmin()): ?>
                        <a href="/admin" class="border-transparent text-gray-900 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Admin
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-sm text-gray-700"><?= htmlspecialchars($auth->user()['name']) ?></span>
                        <span class="ml-2 text-xs text-gray-500">(<?= htmlspecialchars($auth->role()) ?>)</span>
                    </div>
                    <div class="ml-4">
                        <a href="/logout" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main>
