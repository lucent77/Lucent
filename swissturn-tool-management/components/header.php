<?php
/**
 * Swissturn Tool Management System
 * Header Component
 */

if (!defined('SYSTEM_INIT')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/custom.css">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
