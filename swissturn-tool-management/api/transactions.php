<?php
/**
 * Swissturn Tool Management System
 * Transactions API Endpoint
 *
 * Handles tool check-out and check-in operations
 */

define('SYSTEM_INIT', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../auth/session.php';

header('Content-Type: application/json');

// Require login for all operations
if (!isLoggedIn()) {
    jsonResponse(false, null, MSG_SESSION_EXPIRED, HTTP_UNAUTHORIZED);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet();
            break;

        case 'POST':
            handlePost();
            break;

        default:
            jsonResponse(false, null, 'Method not allowed', HTTP_METHOD_NOT_ALLOWED);
    }
} catch (Exception $e) {
    error_log("Transactions API Error: " . $e->getMessage());
    jsonResponse(false, null, MSG_ERROR_DATABASE, HTTP_INTERNAL_ERROR);
}

/**
 * GET - Retrieve transactions
 */
function handleGet() {
    $where = [];
    $params = [];

    // Filter by user (workers can only see their own)
    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);

        // Workers can only view their own transactions
        if (hasRole(ROLE_WORKER) && $user_id != getCurrentUserId()) {
            jsonResponse(false, null, MSG_ACCESS_DENIED, HTTP_FORBIDDEN);
        }

        $where[] = "t.user_id = ?";
        $params[] = $user_id;
    } elseif (hasRole(ROLE_WORKER)) {
        // Workers see only their own by default
        $where[] = "t.user_id = ?";
        $params[] = getCurrentUserId();
    }

    // Filter by tool
    if (isset($_GET['tool_id'])) {
        $where[] = "t.tool_id = ?";
        $params[] = intval($_GET['tool_id']);
    }

    // Filter by transaction type
    if (isset($_GET['type'])) {
        $where[] = "t.transaction_type = ?";
        $params[] = $_GET['type'];
    }

    // Filter by job ID
    if (isset($_GET['job_id'])) {
        $where[] = "t.job_id = ?";
        $params[] = $_GET['job_id'];
    }

    // Filter by date range
    if (isset($_GET['from_date'])) {
        $where[] = "t.transaction_date >= ?";
        $params[] = $_GET['from_date'];
    }

    if (isset($_GET['to_date'])) {
        $where[] = "t.transaction_date <= ?";
        $params[] = $_GET['to_date'] . ' 23:59:59';
    }

    $sql = "SELECT t.*,
                   tool.tool_name, tool.tool_size,
                   u.full_name as user_name, u.username
            FROM transactions t
            INNER JOIN tools tool ON t.tool_id = tool.tool_id
            INNER JOIN users u ON t.user_id = u.user_id";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY t.transaction_date DESC";

    // Limit results
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    $sql .= " LIMIT " . $limit;

    $transactions = dbQuery($sql, $params);

    jsonResponse(true, $transactions, 'Transactions retrieved successfully');
}

/**
 * POST - Create transaction (checkout or checkin)
 */
function handlePost() {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    $validation = validateTransactionData($data);
    if (!$validation['valid']) {
        jsonResponse(false, null, implode(' ', $validation['errors']), HTTP_BAD_REQUEST);
    }

    $tool_id = intval($data['tool_id']);
    $transaction_type = $data['transaction_type'];
    $quantity = intval($data['quantity'] ?? 1);
    $user_id = getCurrentUserId();

    // Get tool information
    $tool = dbQuery("SELECT * FROM tools WHERE tool_id = ?", [$tool_id]);
    if (empty($tool)) {
        jsonResponse(false, null, 'Tool not found', HTTP_NOT_FOUND);
    }
    $tool = $tool[0];

    // Calculate current status
    $tool_status = calculateToolStatus($tool);

    // Begin transaction
    dbBeginTransaction();

    try {
        if ($transaction_type === TRANSACTION_CHECKOUT) {
            // Validate checkout
            if ($tool_status === TOOL_STATUS_EXPIRED) {
                throw new Exception(MSG_TOOL_EXPIRED);
            }

            if ($tool['current_stock'] < $quantity) {
                throw new Exception(MSG_TOOL_OUT_OF_STOCK);
            }

            // Update stock
            $new_stock = $tool['current_stock'] - $quantity;
            dbExecute("UPDATE tools SET current_stock = ? WHERE tool_id = ?", [$new_stock, $tool_id]);

            // Set first use date if not set
            if (empty($tool['first_use_date'])) {
                dbExecute("UPDATE tools SET first_use_date = CURDATE() WHERE tool_id = ?", [$tool_id]);
            }

            // Increment usage count
            dbExecute("UPDATE tools SET usage_count = usage_count + ? WHERE tool_id = ?", [$quantity, $tool_id]);

            $message = MSG_TOOL_CHECKOUT_SUCCESS;

        } elseif ($transaction_type === TRANSACTION_CHECKIN) {
            // Update stock
            $new_stock = $tool['current_stock'] + $quantity;
            dbExecute("UPDATE tools SET current_stock = ? WHERE tool_id = ?", [$new_stock, $tool_id]);

            $message = MSG_TOOL_CHECKIN_SUCCESS;
        }

        // Create transaction record
        $sql = "INSERT INTO transactions (
                    tool_id, user_id, transaction_type, job_id,
                    task_description, quantity, notes
                ) VALUES (
                    :tool_id, :user_id, :transaction_type, :job_id,
                    :task_description, :quantity, :notes
                )";

        $params = [
            ':tool_id' => $tool_id,
            ':user_id' => $user_id,
            ':transaction_type' => $transaction_type,
            ':job_id' => $data['job_id'] ?? null,
            ':task_description' => $data['task_description'] ?? null,
            ':quantity' => $quantity,
            ':notes' => $data['notes'] ?? null
        ];

        dbExecute($sql, $params);
        $transaction_id = dbLastInsertId();

        // Log action
        $action = $transaction_type === TRANSACTION_CHECKOUT ? 'CHECKOUT_TOOL' : 'CHECKIN_TOOL';
        logAudit($user_id, $action, 'transactions', $transaction_id, null, $data);

        // Check if auto-reorder needed
        $updated_tool = dbQuery("SELECT * FROM tools WHERE tool_id = ?", [$tool_id])[0];
        if (needsAutoReorder($updated_tool)) {
            createAutoPurchaseOrder($tool_id, $user_id);
        }

        // Commit transaction
        dbCommit();

        jsonResponse(true, ['transaction_id' => $transaction_id], $message, HTTP_CREATED);

    } catch (Exception $e) {
        // Rollback on error
        dbRollback();
        throw $e;
    }
}
