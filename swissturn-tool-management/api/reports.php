<?php
/**
 * Swissturn Tool Management System
 * Reports API Endpoint
 *
 * Generates various reports and statistics
 */

define('SYSTEM_INIT', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/session.php';

header('Content-Type: application/json');

// Require admin role for all reports
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(false, null, 'Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

$report_type = $_GET['type'] ?? '';

try {
    switch ($report_type) {
        case 'usage':
            handleUsageReport();
            break;

        case 'top_tools':
            handleTopToolsReport();
            break;

        case 'reorder':
            handleReorderReport();
            break;

        case 'lifecycle':
            handleLifecycleReport();
            break;

        case 'audit':
            handleAuditReport();
            break;

        case 'dashboard':
            handleDashboardStats();
            break;

        default:
            jsonResponse(false, null, 'Invalid report type', HTTP_BAD_REQUEST);
    }
} catch (Exception $e) {
    error_log("Reports API Error: " . $e->getMessage());
    jsonResponse(false, null, MSG_ERROR_DATABASE, HTTP_INTERNAL_ERROR);
}

/**
 * Tool Usage Trends Report
 * Prevents chart from being infinitely long by limiting to CHART_MAX_DATA_POINTS
 */
function handleUsageReport() {
    $days = isset($_GET['days']) ? min(intval($_GET['days']), CHART_DEFAULT_DAYS) : CHART_DEFAULT_DAYS;

    // Get daily transaction counts (limited to prevent infinite charts)
    $sql = "SELECT DATE(transaction_date) as date,
                   COUNT(*) as total_transactions,
                   SUM(CASE WHEN transaction_type = 'checkout' THEN 1 ELSE 0 END) as checkouts,
                   SUM(CASE WHEN transaction_type = 'checkin' THEN 1 ELSE 0 END) as checkins
            FROM transactions
            WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(transaction_date)
            ORDER BY date ASC
            LIMIT ?";

    $data = dbQuery($sql, [$days, CHART_MAX_DATA_POINTS]);

    jsonResponse(true, $data, 'Usage report generated successfully');
}

/**
 * Top Tools Report
 */
function handleTopToolsReport() {
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 50) : 20;

    // Most frequently used tools
    $sql = "SELECT t.tool_id, t.tool_name, t.tool_size, c.category_name,
                   COUNT(tr.transaction_id) as transaction_count,
                   SUM(CASE WHEN tr.transaction_type = 'checkout' THEN tr.quantity ELSE 0 END) as total_checkouts
            FROM tools t
            LEFT JOIN tool_categories c ON t.category_id = c.category_id
            LEFT JOIN transactions tr ON t.tool_id = tr.tool_id
            GROUP BY t.tool_id
            ORDER BY transaction_count DESC, total_checkouts DESC
            LIMIT ?";

    $data = dbQuery($sql, [$limit]);

    jsonResponse(true, $data, 'Top tools report generated successfully');
}

/**
 * Reorder Report
 */
function handleReorderReport() {
    // Tools that need reordering
    $sql = "SELECT t.*, c.category_name, s.supplier_name
            FROM tools t
            LEFT JOIN tool_categories c ON t.category_id = c.category_id
            LEFT JOIN suppliers s ON t.supplier_id = s.supplier_id
            WHERE t.current_stock <= t.minimum_stock
            ORDER BY (t.current_stock - t.minimum_stock) ASC, t.tool_name ASC";

    $tools = dbQuery($sql);

    // Calculate status and add reorder info
    foreach ($tools as &$tool) {
        $tool['status'] = calculateToolStatus($tool);
        $tool['suggested_order_quantity'] = max(10, $tool['minimum_stock'] * 2);
        $tool['stock_deficit'] = $tool['minimum_stock'] - $tool['current_stock'];
    }

    jsonResponse(true, $tools, 'Reorder report generated successfully');
}

/**
 * Lifecycle Report
 */
function handleLifecycleReport() {
    // Get all tools with first use date
    $sql = "SELECT t.*, c.category_name, s.supplier_name
            FROM tools t
            LEFT JOIN tool_categories c ON t.category_id = c.category_id
            LEFT JOIN suppliers s ON t.supplier_id = s.supplier_id
            WHERE t.first_use_date IS NOT NULL
            ORDER BY t.tool_name ASC";

    $tools = dbQuery($sql);

    $lifecycle_data = [];

    foreach ($tools as $tool) {
        $status = calculateToolStatus($tool);
        $percentage = getToolLifecyclePercentage($tool);

        // Calculate remaining lifespan
        $remaining = 0;
        $remaining_unit = '';

        if ($tool['lifespan_type'] === LIFESPAN_TYPE_TIME) {
            $first_use = new DateTime($tool['first_use_date']);
            $today = new DateTime();
            $days_used = $today->diff($first_use)->days;
            $remaining = max(0, $tool['lifespan_limit'] - $days_used);
            $remaining_unit = 'days';
        } elseif ($tool['lifespan_type'] === LIFESPAN_TYPE_USAGE) {
            $remaining = max(0, $tool['lifespan_limit'] - $tool['usage_count']);
            $remaining_unit = 'uses';
        }

        $lifecycle_data[] = [
            'tool_id' => $tool['tool_id'],
            'tool_name' => $tool['tool_name'],
            'tool_size' => $tool['tool_size'],
            'category_name' => $tool['category_name'],
            'supplier_name' => $tool['supplier_name'],
            'lifespan_type' => $tool['lifespan_type'],
            'lifespan_limit' => $tool['lifespan_limit'],
            'usage_count' => $tool['usage_count'],
            'first_use_date' => $tool['first_use_date'],
            'lifecycle_percentage' => round($percentage, 2),
            'remaining' => $remaining,
            'remaining_unit' => $remaining_unit,
            'status' => $status
        ];
    }

    // Sort by lifecycle percentage (highest first)
    usort($lifecycle_data, function($a, $b) {
        return $b['lifecycle_percentage'] <=> $a['lifecycle_percentage'];
    });

    jsonResponse(true, $lifecycle_data, 'Lifecycle report generated successfully');
}

/**
 * Audit Trail Report
 */
function handleAuditReport() {
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 200) : 100;

    $where = [];
    $params = [];

    if (isset($_GET['user_id'])) {
        $where[] = "al.user_id = ?";
        $params[] = intval($_GET['user_id']);
    }

    if (isset($_GET['action'])) {
        $where[] = "al.action = ?";
        $params[] = $_GET['action'];
    }

    if (isset($_GET['from_date'])) {
        $where[] = "al.created_at >= ?";
        $params[] = $_GET['from_date'];
    }

    if (isset($_GET['to_date'])) {
        $where[] = "al.created_at <= ?";
        $params[] = $_GET['to_date'] . ' 23:59:59';
    }

    $sql = "SELECT al.*, u.full_name as user_name, u.username
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.user_id";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY al.created_at DESC LIMIT ?";
    $params[] = $limit;

    $logs = dbQuery($sql, $params);

    jsonResponse(true, $logs, 'Audit report generated successfully');
}

/**
 * Dashboard Statistics
 */
function handleDashboardStats() {
    $stats = [];

    // Total tools
    $result = dbQuery("SELECT COUNT(*) as count FROM tools");
    $stats['total_tools'] = $result[0]['count'];

    // Active tools
    $result = dbQuery("SELECT COUNT(*) as count FROM tools WHERE current_stock > 0");
    $stats['active_tools'] = $result[0]['count'];

    // Low stock tools
    $result = dbQuery("SELECT COUNT(*) as count FROM tools WHERE current_stock <= minimum_stock");
    $stats['low_stock_tools'] = $result[0]['count'];

    // Tools near expiry or expired
    $all_tools = dbQuery("SELECT * FROM tools WHERE first_use_date IS NOT NULL");
    $near_expiry = 0;
    $expired = 0;
    foreach ($all_tools as $tool) {
        $status = calculateToolStatus($tool);
        if ($status === TOOL_STATUS_NEAR_EXPIRY) $near_expiry++;
        if ($status === TOOL_STATUS_EXPIRED) $expired++;
    }
    $stats['tools_near_expiry'] = $near_expiry;
    $stats['tools_expired'] = $expired;

    // Total users
    $result = dbQuery("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $stats['total_users'] = $result[0]['count'];

    // Transactions this month
    $result = dbQuery("SELECT COUNT(*) as count FROM transactions
                       WHERE MONTH(transaction_date) = MONTH(NOW())
                       AND YEAR(transaction_date) = YEAR(NOW())");
    $stats['transactions_this_month'] = $result[0]['count'];

    // Pending purchase orders
    $result = dbQuery("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'pending'");
    $stats['pending_orders'] = $result[0]['count'];

    // Recent transactions (limited to 10)
    $recent = dbQuery("SELECT t.*, tool.tool_name, u.full_name as user_name
                       FROM transactions t
                       INNER JOIN tools tool ON t.tool_id = tool.tool_id
                       INNER JOIN users u ON t.user_id = u.user_id
                       ORDER BY t.transaction_date DESC
                       LIMIT 10");
    $stats['recent_transactions'] = $recent;

    // Category distribution (limited for chart)
    $categories = dbQuery("SELECT c.category_name, COUNT(t.tool_id) as tool_count
                           FROM tool_categories c
                           LEFT JOIN tools t ON c.category_id = t.category_id
                           GROUP BY c.category_id
                           ORDER BY tool_count DESC
                           LIMIT ?", [CHART_MAX_DATA_POINTS]);
    $stats['category_distribution'] = $categories;

    jsonResponse(true, $stats, 'Dashboard statistics generated successfully');
}
