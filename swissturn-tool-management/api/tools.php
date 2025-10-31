<?php
/**
 * Swissturn Tool Management System
 * Tools API Endpoint
 *
 * Handles CRUD operations for tools
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
            requireAdmin();
            handlePost();
            break;

        case 'PUT':
            requireAdmin();
            handlePut();
            break;

        case 'DELETE':
            requireAdmin();
            handleDelete();
            break;

        default:
            jsonResponse(false, null, 'Method not allowed', HTTP_METHOD_NOT_ALLOWED);
    }
} catch (Exception $e) {
    error_log("Tools API Error: " . $e->getMessage());
    jsonResponse(false, null, MSG_ERROR_DATABASE, HTTP_INTERNAL_ERROR);
}

/**
 * GET - Retrieve tools
 */
function handleGet() {
    if (isset($_GET['id'])) {
        // Get single tool
        $tool_id = intval($_GET['id']);
        $sql = "SELECT t.*, c.category_name, s.supplier_name
                FROM tools t
                LEFT JOIN tool_categories c ON t.category_id = c.category_id
                LEFT JOIN suppliers s ON t.supplier_id = s.supplier_id
                WHERE t.tool_id = ?";

        $result = dbQuery($sql, [$tool_id]);

        if (empty($result)) {
            jsonResponse(false, null, 'Tool not found', HTTP_NOT_FOUND);
        }

        $tool = $result[0];
        $tool['status'] = calculateToolStatus($tool);
        $tool['lifecycle_percentage'] = getToolLifecyclePercentage($tool);

        jsonResponse(true, $tool, 'Tool retrieved successfully');

    } else {
        // Get all tools or filtered tools
        $where = [];
        $params = [];

        if (isset($_GET['status'])) {
            // Note: Status is calculated, so we filter after retrieval
            $filter_status = $_GET['status'];
        }

        if (isset($_GET['category_id'])) {
            $where[] = "t.category_id = ?";
            $params[] = intval($_GET['category_id']);
        }

        if (isset($_GET['search'])) {
            $where[] = "(t.tool_name LIKE ? OR t.tool_description LIKE ?)";
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $sql = "SELECT t.*, c.category_name, s.supplier_name
                FROM tools t
                LEFT JOIN tool_categories c ON t.category_id = c.category_id
                LEFT JOIN suppliers s ON t.supplier_id = s.supplier_id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY t.tool_name ASC";

        $tools = dbQuery($sql, $params);

        // Calculate status for each tool
        foreach ($tools as &$tool) {
            $tool['status'] = calculateToolStatus($tool);
            $tool['lifecycle_percentage'] = getToolLifecyclePercentage($tool);
        }

        // Filter by status if requested
        if (isset($filter_status)) {
            $tools = array_filter($tools, function($tool) use ($filter_status) {
                return $tool['status'] === $filter_status;
            });
            $tools = array_values($tools); // Re-index array
        }

        jsonResponse(true, $tools, 'Tools retrieved successfully');
    }
}

/**
 * POST - Create new tool
 */
function handlePost() {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    $validation = validateToolData($data);
    if (!$validation['valid']) {
        jsonResponse(false, null, implode(' ', $validation['errors']), HTTP_BAD_REQUEST);
    }

    // Insert tool
    $sql = "INSERT INTO tools (
                category_id, tool_name, tool_description, tool_size,
                supplier_id, supplier_model_number, current_stock, minimum_stock,
                lifespan_type, lifespan_limit, status
            ) VALUES (
                :category_id, :tool_name, :tool_description, :tool_size,
                :supplier_id, :supplier_model_number, :current_stock, :minimum_stock,
                :lifespan_type, :lifespan_limit, :status
            )";

    $params = [
        ':category_id' => $data['category_id'],
        ':tool_name' => $data['tool_name'],
        ':tool_description' => $data['tool_description'] ?? null,
        ':tool_size' => $data['tool_size'] ?? null,
        ':supplier_id' => $data['supplier_id'] ?? null,
        ':supplier_model_number' => $data['supplier_model_number'] ?? null,
        ':current_stock' => $data['current_stock'] ?? 0,
        ':minimum_stock' => $data['minimum_stock'] ?? 5,
        ':lifespan_type' => $data['lifespan_type'],
        ':lifespan_limit' => $data['lifespan_limit'],
        ':status' => TOOL_STATUS_ACTIVE
    ];

    dbExecute($sql, $params);
    $tool_id = dbLastInsertId();

    // Log action
    logAudit(getCurrentUserId(), 'CREATE_TOOL', 'tools', $tool_id, null, $data);

    jsonResponse(true, ['tool_id' => $tool_id], MSG_TOOL_CREATED, HTTP_CREATED);
}

/**
 * PUT - Update tool
 */
function handlePut() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['tool_id'])) {
        jsonResponse(false, null, 'Tool ID is required', HTTP_BAD_REQUEST);
    }

    $tool_id = intval($data['tool_id']);

    // Get old values
    $old_tool = dbQuery("SELECT * FROM tools WHERE tool_id = ?", [$tool_id]);
    if (empty($old_tool)) {
        jsonResponse(false, null, 'Tool not found', HTTP_NOT_FOUND);
    }

    // Validate input
    $validation = validateToolData($data);
    if (!$validation['valid']) {
        jsonResponse(false, null, implode(' ', $validation['errors']), HTTP_BAD_REQUEST);
    }

    // Update tool
    $sql = "UPDATE tools SET
                category_id = :category_id,
                tool_name = :tool_name,
                tool_description = :tool_description,
                tool_size = :tool_size,
                supplier_id = :supplier_id,
                supplier_model_number = :supplier_model_number,
                current_stock = :current_stock,
                minimum_stock = :minimum_stock,
                lifespan_type = :lifespan_type,
                lifespan_limit = :lifespan_limit,
                updated_at = NOW()
            WHERE tool_id = :tool_id";

    $params = [
        ':tool_id' => $tool_id,
        ':category_id' => $data['category_id'],
        ':tool_name' => $data['tool_name'],
        ':tool_description' => $data['tool_description'] ?? null,
        ':tool_size' => $data['tool_size'] ?? null,
        ':supplier_id' => $data['supplier_id'] ?? null,
        ':supplier_model_number' => $data['supplier_model_number'] ?? null,
        ':current_stock' => $data['current_stock'] ?? 0,
        ':minimum_stock' => $data['minimum_stock'] ?? 5,
        ':lifespan_type' => $data['lifespan_type'],
        ':lifespan_limit' => $data['lifespan_limit']
    ];

    dbExecute($sql, $params);

    // Log action
    logAudit(getCurrentUserId(), 'UPDATE_TOOL', 'tools', $tool_id, $old_tool[0], $data);

    jsonResponse(true, ['tool_id' => $tool_id], MSG_TOOL_UPDATED);
}

/**
 * DELETE - Delete tool
 */
function handleDelete() {
    if (!isset($_GET['id'])) {
        jsonResponse(false, null, 'Tool ID is required', HTTP_BAD_REQUEST);
    }

    $tool_id = intval($_GET['id']);

    // Check if tool exists
    $tool = dbQuery("SELECT * FROM tools WHERE tool_id = ?", [$tool_id]);
    if (empty($tool)) {
        jsonResponse(false, null, 'Tool not found', HTTP_NOT_FOUND);
    }

    // Delete tool (transactions will be cascaded)
    dbExecute("DELETE FROM tools WHERE tool_id = ?", [$tool_id]);

    // Log action
    logAudit(getCurrentUserId(), 'DELETE_TOOL', 'tools', $tool_id, $tool[0], null);

    jsonResponse(true, null, MSG_TOOL_DELETED);
}
