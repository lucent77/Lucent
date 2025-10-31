<?php
/**
 * Swissturn Tool Management System
 * Input Validation Functions
 *
 * This file contains validation functions for user inputs
 */

/**
 * Validate email address
 *
 * @param string $email Email address
 * @return bool True if valid
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 *
 * @param string $password Password
 * @return array ['valid' => bool, 'message' => string]
 */
function validatePassword($password) {
    $result = ['valid' => true, 'message' => ''];

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $result['valid'] = false;
        $result['message'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        return $result;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one uppercase letter.';
        return $result;
    }

    if (!preg_match('/[a-z]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one lowercase letter.';
        return $result;
    }

    if (!preg_match('/[0-9]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one number.';
        return $result;
    }

    return $result;
}

/**
 * Validate required field
 *
 * @param mixed $value Value to check
 * @param string $field_name Field name for error message
 * @return array ['valid' => bool, 'message' => string]
 */
function validateRequired($value, $field_name = 'Field') {
    $result = ['valid' => true, 'message' => ''];

    if (empty($value) && $value !== '0') {
        $result['valid'] = false;
        $result['message'] = $field_name . ' is required.';
    }

    return $result;
}

/**
 * Validate integer value
 *
 * @param mixed $value Value to check
 * @param string $field_name Field name for error message
 * @param int $min Minimum value (optional)
 * @param int $max Maximum value (optional)
 * @return array ['valid' => bool, 'message' => string]
 */
function validateInteger($value, $field_name = 'Field', $min = null, $max = null) {
    $result = ['valid' => true, 'message' => ''];

    if (!is_numeric($value) || intval($value) != $value) {
        $result['valid'] = false;
        $result['message'] = $field_name . ' must be an integer.';
        return $result;
    }

    $intValue = intval($value);

    if ($min !== null && $intValue < $min) {
        $result['valid'] = false;
        $result['message'] = $field_name . ' must be at least ' . $min . '.';
        return $result;
    }

    if ($max !== null && $intValue > $max) {
        $result['valid'] = false;
        $result['message'] = $field_name . ' must not exceed ' . $max . '.';
        return $result;
    }

    return $result;
}

/**
 * Validate string length
 *
 * @param string $value Value to check
 * @param string $field_name Field name for error message
 * @param int $min_length Minimum length
 * @param int $max_length Maximum length
 * @return array ['valid' => bool, 'message' => string]
 */
function validateLength($value, $field_name = 'Field', $min_length = 0, $max_length = PHP_INT_MAX) {
    $result = ['valid' => true, 'message' => ''];
    $length = strlen($value);

    if ($length < $min_length) {
        $result['valid'] = false;
        $result['message'] = $field_name . ' must be at least ' . $min_length . ' characters.';
        return $result;
    }

    if ($length > $max_length) {
        $result['valid'] = false;
        $result['message'] = $field_name . ' must not exceed ' . $max_length . ' characters.';
        return $result;
    }

    return $result;
}

/**
 * Validate enum value
 *
 * @param mixed $value Value to check
 * @param array $allowed_values Allowed values
 * @param string $field_name Field name for error message
 * @return array ['valid' => bool, 'message' => string]
 */
function validateEnum($value, $allowed_values, $field_name = 'Field') {
    $result = ['valid' => true, 'message' => ''];

    if (!in_array($value, $allowed_values, true)) {
        $result['valid'] = false;
        $result['message'] = $field_name . ' has an invalid value.';
    }

    return $result;
}

/**
 * Validate date format
 *
 * @param string $date Date string
 * @param string $format Date format (default: Y-m-d)
 * @param string $field_name Field name for error message
 * @return array ['valid' => bool, 'message' => string]
 */
function validateDate($date, $format = 'Y-m-d', $field_name = 'Date') {
    $result = ['valid' => true, 'message' => ''];

    $d = DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
        $result['valid'] = false;
        $result['message'] = $field_name . ' is not a valid date.';
    }

    return $result;
}

/**
 * Validate username
 *
 * @param string $username Username
 * @return array ['valid' => bool, 'message' => string]
 */
function validateUsername($username) {
    $result = ['valid' => true, 'message' => ''];

    // Check length
    $lengthCheck = validateLength($username, 'Username', 3, 50);
    if (!$lengthCheck['valid']) {
        return $lengthCheck;
    }

    // Check format (alphanumeric and underscore only)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $result['valid'] = false;
        $result['message'] = 'Username can only contain letters, numbers, and underscores.';
        return $result;
    }

    return $result;
}

/**
 * Validate tool data
 *
 * @param array $data Tool data
 * @return array ['valid' => bool, 'errors' => array]
 */
function validateToolData($data) {
    $errors = [];

    // Tool name
    $check = validateRequired($data['tool_name'] ?? '', 'Tool name');
    if (!$check['valid']) $errors[] = $check['message'];

    $check = validateLength($data['tool_name'] ?? '', 'Tool name', 1, 100);
    if (!$check['valid']) $errors[] = $check['message'];

    // Category ID
    $check = validateRequired($data['category_id'] ?? '', 'Category');
    if (!$check['valid']) $errors[] = $check['message'];

    $check = validateInteger($data['category_id'] ?? '', 'Category', 1);
    if (!$check['valid']) $errors[] = $check['message'];

    // Supplier ID
    if (!empty($data['supplier_id'])) {
        $check = validateInteger($data['supplier_id'], 'Supplier', 1);
        if (!$check['valid']) $errors[] = $check['message'];
    }

    // Stock quantities
    $check = validateInteger($data['current_stock'] ?? 0, 'Current stock', 0);
    if (!$check['valid']) $errors[] = $check['message'];

    $check = validateInteger($data['minimum_stock'] ?? 0, 'Minimum stock', 0);
    if (!$check['valid']) $errors[] = $check['message'];

    // Lifespan type
    $check = validateEnum(
        $data['lifespan_type'] ?? '',
        [LIFESPAN_TYPE_TIME, LIFESPAN_TYPE_USAGE],
        'Lifespan type'
    );
    if (!$check['valid']) $errors[] = $check['message'];

    // Lifespan limit
    $check = validateInteger($data['lifespan_limit'] ?? 0, 'Lifespan limit', 1);
    if (!$check['valid']) $errors[] = $check['message'];

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate user data
 *
 * @param array $data User data
 * @param bool $is_update Is this an update operation
 * @return array ['valid' => bool, 'errors' => array]
 */
function validateUserData($data, $is_update = false) {
    $errors = [];

    // Username
    $check = validateRequired($data['username'] ?? '', 'Username');
    if (!$check['valid']) $errors[] = $check['message'];

    $check = validateUsername($data['username'] ?? '');
    if (!$check['valid']) $errors[] = $check['message'];

    // Password (only required for new users)
    if (!$is_update || !empty($data['password'])) {
        $check = validateRequired($data['password'] ?? '', 'Password');
        if (!$check['valid']) $errors[] = $check['message'];

        $check = validatePassword($data['password'] ?? '');
        if (!$check['valid']) $errors[] = $check['message'];
    }

    // Full name
    $check = validateRequired($data['full_name'] ?? '', 'Full name');
    if (!$check['valid']) $errors[] = $check['message'];

    $check = validateLength($data['full_name'] ?? '', 'Full name', 1, 100);
    if (!$check['valid']) $errors[] = $check['message'];

    // Email
    $check = validateRequired($data['email'] ?? '', 'Email');
    if (!$check['valid']) $errors[] = $check['message'];

    if (!validateEmail($data['email'] ?? '')) {
        $errors[] = 'Invalid email address.';
    }

    // Role
    $check = validateEnum(
        $data['role'] ?? '',
        [ROLE_ADMIN, ROLE_WORKER],
        'Role'
    );
    if (!$check['valid']) $errors[] = $check['message'];

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate transaction data
 *
 * @param array $data Transaction data
 * @return array ['valid' => bool, 'errors' => array]
 */
function validateTransactionData($data) {
    $errors = [];

    // Tool ID
    $check = validateRequired($data['tool_id'] ?? '', 'Tool');
    if (!$check['valid']) $errors[] = $check['message'];

    $check = validateInteger($data['tool_id'] ?? '', 'Tool', 1);
    if (!$check['valid']) $errors[] = $check['message'];

    // Transaction type
    $check = validateEnum(
        $data['transaction_type'] ?? '',
        [TRANSACTION_CHECKOUT, TRANSACTION_CHECKIN],
        'Transaction type'
    );
    if (!$check['valid']) $errors[] = $check['message'];

    // Quantity
    $check = validateInteger($data['quantity'] ?? 1, 'Quantity', 1);
    if (!$check['valid']) $errors[] = $check['message'];

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate supplier data
 *
 * @param array $data Supplier data
 * @return array ['valid' => bool, 'errors' => array]
 */
function validateSupplierData($data) {
    $errors = [];

    // Supplier name
    $check = validateRequired($data['supplier_name'] ?? '', 'Supplier name');
    if (!$check['valid']) $errors[] = $check['message'];

    $check = validateLength($data['supplier_name'] ?? '', 'Supplier name', 1, 100);
    if (!$check['valid']) $errors[] = $check['message'];

    // Email (optional but must be valid if provided)
    if (!empty($data['email'])) {
        if (!validateEmail($data['email'])) {
            $errors[] = 'Invalid email address.';
        }
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
