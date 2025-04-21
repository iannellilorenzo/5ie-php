<?php
/**
 * Send a JSON response with the given status code and data
 */
function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Send a successful response with data
 */
function sendSuccess($data = [], $message = 'Success') {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    sendResponse(200, $response);
}

/**
 * Send an error response
 */
function sendError($message = 'Error', $statusCode = 400) {
    sendResponse($statusCode, [
        'success' => false,
        'error' => $message
    ]);
}