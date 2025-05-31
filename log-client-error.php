<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Log file path
$log_file = '/xampp/htdocs/MedStudy-Space-System/client_errors.log';

try {
    // Get raw input
    $raw_input = file_get_contents('php://input');
    
    // Decode JSON input
    $error_data = json_decode($raw_input, true);
    
    // Validate input
    if (!$error_data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    // Prepare log message
    $log_message = date('[Y-m-d H:i:s] ') . 
        "Client Error - Context: " . ($error_data['context'] ?? 'Unknown') . "\n" .
        "Error Details: " . json_encode($error_data['errorDetails'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Append to log file
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Send success response
    echo json_encode(['success' => true, 'message' => 'Error logged']);
} catch (Exception $e) {
    // Handle any unexpected errors
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error logging failed: ' . $e->getMessage()
    ]);
}
?> 