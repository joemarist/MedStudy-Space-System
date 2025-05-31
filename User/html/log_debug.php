<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure clean JSON response
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

try {
    // Get raw POST data
    $rawInput = file_get_contents('php://input');
    $logEntry = json_decode($rawInput, true);

    if (!$logEntry) {
        throw new Exception('Invalid log entry');
    }

    // Define log directory and file
    $logDir = dirname(__FILE__) . '/../logs';
    $logFile = $logDir . '/js_debug_log.txt';

    // Ensure log directory exists
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Prepare log message
    $logMessage = sprintf(
        "[%s] Category: %s, Message: %s, Details: %s\n", 
        $logEntry['timestamp'] ?? date('Y-m-d H:i:s'),
        $logEntry['category'] ?? 'UNKNOWN',
        $logEntry['message'] ?? 'No message',
        json_encode($logEntry)
    );

    // Write to log file
    $result = file_put_contents($logFile, $logMessage, FILE_APPEND);

    if ($result === false) {
        throw new Exception('Failed to write log file');
    }

    // Respond with success
    echo json_encode([
        'status' => 'success',
        'message' => 'Log entry recorded'
    ]);

} catch (Exception $e) {
    // Error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit;
?> 