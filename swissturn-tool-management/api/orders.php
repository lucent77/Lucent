<?php
/**
 * Swissturn Tool Management System
 * Purchase Orders API Endpoint
 *
 * Handles purchase order management
 */

define('SYSTEM_INIT', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../auth/session.php';

header('Content-Type: application/json');

// Require admin role for all operations
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet();
            break;

        case 'POST':
            handlePost();
            break;

        case 'PUT':
            handlePut();
            break;

        case 'DELETE':
            handleDelete();
            break;

        default:
            jsonResponse(false, null, 'Method not allowed', HTTP_METHOD_NOT_ALLOWED);
    }
} catch (Exception $e) {
    error_log("Orders API Error: " . $e->getMessage());
    jsonResponse(false, null, MSG_ERROR_DATABASE, HTTP_INTERNAL_ERROR);
}

/**
 * GET - Retrieve purchase orders
 */
function handleGet() {
    $where = [];
    $params = [];

    // Filter by status
    if (isset($_GET['status'])) {
        $where[] = "po.status = ?";
        $params[] = $_GET['status'];
    }

    // Filter by priority
    if (isset($_GET['priority'])) {
        $where[] = "po.priority = ?";
        $params[] = $_GET['priority'];
    }

    // Filter by tool
    if (isset($_GET['tool_id'])) {
        $where[] = "po.tool_id = ?";
        $params[] = intval($_GET['tool_id']);
    }

    // Get auto-generated orders
    if (isset($_GET['auto']) && $_GET['auto'] == '1') {
        $where[] = "po.notes LIKE '%Automatically generated%'";
    }

    $sql = "SELECT po.*,
                   t.tool_name, t.tool_size,
                   s.supplier_name, s.contact_person, s.email as supplier_email,
                   u1.full_name as requested_by_name,
                   u2.full_name as approved_by_name
            FROM purchase_orders po
            INNER JOIN tools t ON po.tool_id = t.tool_id
            INNER JOIN suppliers s ON po.supplier_id = s.supplier_id
            LEFT JOIN users u1 ON po.requested_by = u1.user_id
            LEFT JOIN users u2 ON po.approved_by = u2.user_id";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY po.priority DESC, po.requested_date DESC";

    $orders = dbQuery($sql, $params);

    jsonResponse(true, $orders, 'Purchase orders retrieved successfully');
}

/**
 * POST - Create purchase order
 */
function handlePost() {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (empty($data['tool_id']) || empty($data['supplier_id']) || empty($data['quantity'])) {
        jsonResponse(false, null, 'Tool, supplier, and quantity are required', HTTP_BAD_REQUEST);
    }

    // Validate quantity
    $check = validateInteger($data['quantity'], 'Quantity', 1);
    if (!$check['valid']) {
        jsonResponse(false, null, $check['message'], HTTP_BAD_REQUEST);
    }

    // Validate priority
    $priority = $data['priority'] ?? ORDER_PRIORITY_MEDIUM;
    $check = validateEnum($priority, [
        ORDER_PRIORITY_LOW, ORDER_PRIORITY_MEDIUM, ORDER_PRIORITY_HIGH, ORDER_PRIORITY_URGENT
    ], 'Priority');
    if (!$check['valid']) {
        jsonResponse(false, null, $check['message'], HTTP_BAD_REQUEST);
    }

    // Insert order
    $sql = "INSERT INTO purchase_orders (
                tool_id, supplier_id, quantity, status, priority,
                requested_by, notes
            ) VALUES (
                :tool_id, :supplier_id, :quantity, :status, :priority,
                :requested_by, :notes
            )";

    $params = [
        ':tool_id' => intval($data['tool_id']),
        ':supplier_id' => intval($data['supplier_id']),
        ':quantity' => intval($data['quantity']),
        ':status' => ORDER_STATUS_PENDING,
        ':priority' => $priority,
        ':requested_by' => getCurrentUserId(),
        ':notes' => $data['notes'] ?? null
    ];

    dbExecute($sql, $params);
    $order_id = dbLastInsertId();

    // Log action
    logAudit(getCurrentUserId(), 'CREATE_ORDER', 'purchase_orders', $order_id, null, $data);

    jsonResponse(true, ['order_id' => $order_id], MSG_ORDER_CREATED, HTTP_CREATED);
}

/**
 * PUT - Update purchase order
 */
function handlePut() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['order_id'])) {
        jsonResponse(false, null, 'Order ID is required', HTTP_BAD_REQUEST);
    }

    $order_id = intval($data['order_id']);

    // Get old values
    $old_order = dbQuery("SELECT * FROM purchase_orders WHERE order_id = ?", [$order_id]);
    if (empty($old_order)) {
        jsonResponse(false, null, 'Order not found', HTTP_NOT_FOUND);
    }
    $old_order = $old_order[0];

    // Update order
    $sql = "UPDATE purchase_orders SET ";
    $updates = [];
    $params = [];

    if (isset($data['status'])) {
        $updates[] = "status = ?";
        $params[] = $data['status'];

        // Set approved date and user if status is approved
        if ($data['status'] === ORDER_STATUS_APPROVED) {
            $updates[] = "approved_by = ?";
            $updates[] = "approved_date = NOW()";
            $params[] = getCurrentUserId();
        }
    }

    if (isset($data['priority'])) {
        $updates[] = "priority = ?";
        $params[] = $data['priority'];
    }

    if (isset($data['quantity'])) {
        $updates[] = "quantity = ?";
        $params[] = intval($data['quantity']);
    }

    if (isset($data['notes'])) {
        $updates[] = "notes = ?";
        $params[] = $data['notes'];
    }

    if (empty($updates)) {
        jsonResponse(false, null, 'No updates provided', HTTP_BAD_REQUEST);
    }

    $sql .= implode(", ", $updates);
    $sql .= " WHERE order_id = ?";
    $params[] = $order_id;

    dbExecute($sql, $params);

    // Log action
    $action = isset($data['status']) && $data['status'] === ORDER_STATUS_APPROVED ?
              'APPROVE_ORDER' : 'UPDATE_ORDER';
    logAudit(getCurrentUserId(), $action, 'purchase_orders', $order_id, $old_order, $data);

    $message = isset($data['status']) && $data['status'] === ORDER_STATUS_APPROVED ?
               MSG_ORDER_APPROVED : MSG_ORDER_UPDATED;

    jsonResponse(true, ['order_id' => $order_id], $message);
}

/**
 * DELETE - Cancel purchase order
 */
function handleDelete() {
    if (!isset($_GET['id'])) {
        jsonResponse(false, null, 'Order ID is required', HTTP_BAD_REQUEST);
    }

    $order_id = intval($_GET['id']);

    // Check if order exists
    $order = dbQuery("SELECT * FROM purchase_orders WHERE order_id = ?", [$order_id]);
    if (empty($order)) {
        jsonResponse(false, null, 'Order not found', HTTP_NOT_FOUND);
    }

    // Update status to cancelled instead of deleting
    dbExecute("UPDATE purchase_orders SET status = ? WHERE order_id = ?",
              [ORDER_STATUS_CANCELLED, $order_id]);

    // Log action
    logAudit(getCurrentUserId(), 'CANCEL_ORDER', 'purchase_orders', $order_id, $order[0], null);

    jsonResponse(true, null, MSG_ORDER_CANCELLED);
}
