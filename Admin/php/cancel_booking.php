<?php
session_start();
date_default_timezone_set('Asia/Manila');

// Ensure JSON content type and prevent any HTML output
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/xampp/htdocs/MedStudy-Space-System/error.log');

// Capture any output before sending JSON
ob_start();

// Include notification triggers with error handling
$notification_triggers_path = 'C:/xampp/htdocs/MedStudy-Space-System/User/php/functions/notification_triggers.php';
if (file_exists($notification_triggers_path)) {
    require_once $notification_triggers_path;
} else {
    // Log detailed error about missing file
    logDetailedError("Notification triggers file not found", [
        'expected_path' => $notification_triggers_path,
        'current_script' => __FILE__
    ]);
    
    // Send a generic error response
    sendJsonResponse(false, 'Internal server configuration error', 500);
}

// Database connection parameters
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'medstudy';

// Function to log detailed error
function logDetailedError($message, $context = []) {
    $logMessage = $message;
    if (!empty($context)) {
        $logMessage .= "\nContext: " . json_encode($context);
    }
    error_log($logMessage);
}

// Function to send JSON response and exit
function sendJsonResponse($success, $message, $code = 200, $additionalData = []) {
    // Clear any previous output
    ob_end_clean();
    
    // Set HTTP response code
    http_response_code($code);
    
    // Prepare response
    $response = [
        'success' => $success, 
        'message' => $message
    ];
    
    // Add any additional data
    if (!empty($additionalData)) {
        $response = array_merge($response, $additionalData);
    }
    
    // Send JSON response
    echo json_encode($response);
    exit;
}

// Establish database connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    logDetailedError("Database connection failed", [
        'error' => $conn->connect_error,
        'host' => $host,
        'user' => $user,
        'dbname' => $dbname
    ]);
    sendJsonResponse(false, 'Database connection failed: ' . $conn->connect_error, 500);
}

try {
    // Start a transaction
    $conn->begin_transaction();

    // Get booking details from request
    $raw_input = file_get_contents('php://input');
    
    // Log raw input for debugging
    logDetailedError("Raw input received", ['input' => $raw_input]);
    
    // Validate raw input
    if (empty($raw_input)) {
        logDetailedError("No input data received");
        sendJsonResponse(false, 'No input data received', 400);
    }

    // Decode JSON input
    $data = json_decode($raw_input, true);
    
    // Check JSON decoding
    if (json_last_error() !== JSON_ERROR_NONE) {
        logDetailedError("JSON decoding error", [
            'error' => json_last_error_msg(),
            'raw_input' => $raw_input
        ]);
        sendJsonResponse(false, 'Invalid JSON input: ' . json_last_error_msg(), 400);
    }

    // Validate input
    if (!isset($data['booking_id'])) {
        logDetailedError("Missing booking ID", ['data' => $data]);
        sendJsonResponse(false, 'Missing booking ID', 400);
    }

    $booking_id = $conn->real_escape_string($data['booking_id']);
    $cancellation_reason = $conn->real_escape_string($data['cancel_reason'] ?? 'No reason provided');

    // Fetch booking details before update
    $fetch_query = "
        SELECT b.*, r.room_name, u.user_id 
        FROM booking b 
        JOIN rooms r ON b.room_id = r.room_id 
        JOIN user_details u ON b.user_id = u.user_id 
        WHERE b.book_id = ?
    ";
    $fetch_stmt = $conn->prepare($fetch_query);
    $fetch_stmt->bind_param("i", $booking_id);
    $fetch_stmt->execute();
    $booking_result = $fetch_stmt->get_result();

    if ($booking_result->num_rows === 0) {
        logDetailedError("Booking not found", ['booking_id' => $booking_id]);
        sendJsonResponse(false, 'Booking not found', 404);
    }

    $booking_details = $booking_result->fetch_assoc();
    $fetch_stmt->close();

    // Insert cancellation log
    $log_query = "INSERT INTO cancelledBooking_Logs (
        book_id, 
        cancellation_reason
    ) VALUES (?, ?)";

    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param(
        "is", 
        $booking_details['book_id'],
        $cancellation_reason
    );
    $log_stmt->execute();

    if ($log_stmt->affected_rows === 0) {
        logDetailedError("Failed to log cancellation", [
            'booking_id' => $booking_details['book_id'],
            'reason' => $cancellation_reason
        ]);
        sendJsonResponse(false, 'Failed to log cancellation', 500);
    }
    $log_stmt->close();

    // Update booking status to cancelled by admin
    $update_query = "UPDATE booking SET status = 'cancelled by admin' WHERE book_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $booking_id);
    $update_stmt->execute();

    if ($update_stmt->affected_rows === 0) {
        logDetailedError("Failed to update booking status", [
            'booking_id' => $booking_id,
            'current_status' => $booking_details['status']
        ]);
        sendJsonResponse(false, 'Failed to update booking status', 500);
    }
    $update_stmt->close();

    // Commit transaction
    $conn->commit();

    // Send success response
    sendJsonResponse(true, 'Booking cancelled successfully', 200, [
        'booking_details' => [
            'booking_id' => $booking_id,
            'room_name' => $booking_details['room_name'],
            'booking_date' => $booking_details['booking_date']
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Log the full exception details
    logDetailedError("Booking cancellation exception", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // Send error response
    sendJsonResponse(false, 'An unexpected error occurred: ' . $e->getMessage(), 500);
}

$conn->close();
?>