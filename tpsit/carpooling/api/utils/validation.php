<?php
include_once __DIR__ . '/response.php';

/**
 * Validate required fields in the data
 */
function validateRequired($data, $fields) {
    $missing = [];
    
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        sendError('Required fields missing: ' . implode(', ', $missing), 400);
        return false;
    }
    
    return true;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format', 400);
        return false;
    }
    return true;
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
    } else {
        $data = htmlspecialchars(strip_tags(trim($data)));
    }
    
    return $data;
}