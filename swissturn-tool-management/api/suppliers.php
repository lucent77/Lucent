<?php
/**
 * Swissturn Tool Management System
 * Suppliers API Endpoint
 *
 * Handles supplier management (Admin only)
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
    error_log("Suppliers API Error: " . $e->getMessage());
    jsonResponse(false, null, MSG_ERROR_DATABASE, HTTP_INTERNAL_ERROR);
}

/**
 * GET - Retrieve suppliers
 */
function handleGet() {
    if (isset($_GET['id'])) {
        // Get single supplier
        $supplier_id = intval($_GET['id']);
        $sql = "SELECT * FROM suppliers WHERE supplier_id = ?";

        $result = dbQuery($sql, [$supplier_id]);

        if (empty($result)) {
            jsonResponse(false, null, 'Supplier not found', HTTP_NOT_FOUND);
        }

        jsonResponse(true, $result[0], 'Supplier retrieved successfully');

    } else {
        // Get all suppliers
        $where = [];
        $params = [];

        if (isset($_GET['is_active'])) {
            $where[] = "is_active = ?";
            $params[] = intval($_GET['is_active']);
        }

        $sql = "SELECT * FROM suppliers";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY supplier_name ASC";

        $suppliers = dbQuery($sql, $params);

        jsonResponse(true, $suppliers, 'Suppliers retrieved successfully');
    }
}

/**
 * POST - Create new supplier
 */
function handlePost() {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    $validation = validateSupplierData($data);
    if (!$validation['valid']) {
        jsonResponse(false, null, implode(' ', $validation['errors']), HTTP_BAD_REQUEST);
    }

    // Insert supplier
    $sql = "INSERT INTO suppliers (
                supplier_name, contact_person, email, phone, address, is_active
            ) VALUES (
                :supplier_name, :contact_person, :email, :phone, :address, :is_active
            )";

    $params = [
        ':supplier_name' => $data['supplier_name'],
        ':contact_person' => $data['contact_person'] ?? null,
        ':email' => $data['email'] ?? null,
        ':phone' => $data['phone'] ?? null,
        ':address' => $data['address'] ?? null,
        ':is_active' => isset($data['is_active']) ? intval($data['is_active']) : 1
    ];

    dbExecute($sql, $params);
    $supplier_id = dbLastInsertId();

    // Log action
    logAudit(getCurrentUserId(), 'CREATE_SUPPLIER', 'suppliers', $supplier_id, null, $data);

    jsonResponse(true, ['supplier_id' => $supplier_id], MSG_SUPPLIER_CREATED, HTTP_CREATED);
}

/**
 * PUT - Update supplier
 */
function handlePut() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['supplier_id'])) {
        jsonResponse(false, null, 'Supplier ID is required', HTTP_BAD_REQUEST);
    }

    $supplier_id = intval($data['supplier_id']);

    // Get old values
    $old_supplier = dbQuery("SELECT * FROM suppliers WHERE supplier_id = ?", [$supplier_id]);
    if (empty($old_supplier)) {
        jsonResponse(false, null, 'Supplier not found', HTTP_NOT_FOUND);
    }

    // Validate input
    $validation = validateSupplierData($data);
    if (!$validation['valid']) {
        jsonResponse(false, null, implode(' ', $validation['errors']), HTTP_BAD_REQUEST);
    }

    // Update supplier
    $sql = "UPDATE suppliers SET
                supplier_name = :supplier_name,
                contact_person = :contact_person,
                email = :email,
                phone = :phone,
                address = :address,
                is_active = :is_active,
                updated_at = NOW()
            WHERE supplier_id = :supplier_id";

    $params = [
        ':supplier_id' => $supplier_id,
        ':supplier_name' => $data['supplier_name'],
        ':contact_person' => $data['contact_person'] ?? null,
        ':email' => $data['email'] ?? null,
        ':phone' => $data['phone'] ?? null,
        ':address' => $data['address'] ?? null,
        ':is_active' => isset($data['is_active']) ? intval($data['is_active']) : 1
    ];

    dbExecute($sql, $params);

    // Log action
    logAudit(getCurrentUserId(), 'UPDATE_SUPPLIER', 'suppliers', $supplier_id, $old_supplier[0], $data);

    jsonResponse(true, ['supplier_id' => $supplier_id], MSG_SUPPLIER_UPDATED);
}

/**
 * DELETE - Delete supplier
 */
function handleDelete() {
    if (!isset($_GET['id'])) {
        jsonResponse(false, null, 'Supplier ID is required', HTTP_BAD_REQUEST);
    }

    $supplier_id = intval($_GET['id']);

    // Check if supplier exists
    $supplier = dbQuery("SELECT * FROM suppliers WHERE supplier_id = ?", [$supplier_id]);
    if (empty($supplier)) {
        jsonResponse(false, null, 'Supplier not found', HTTP_NOT_FOUND);
    }

    // Check if supplier is used by any tools
    $tools = dbQuery("SELECT COUNT(*) as count FROM tools WHERE supplier_id = ?", [$supplier_id]);
    if ($tools[0]['count'] > 0) {
        jsonResponse(false, null, 'Cannot delete supplier: currently used by ' . $tools[0]['count'] . ' tools',
                    HTTP_BAD_REQUEST);
    }

    // Delete supplier
    dbExecute("DELETE FROM suppliers WHERE supplier_id = ?", [$supplier_id]);

    // Log action
    logAudit(getCurrentUserId(), 'DELETE_SUPPLIER', 'suppliers', $supplier_id, $supplier[0], null);

    jsonResponse(true, null, MSG_SUPPLIER_DELETED);
}
