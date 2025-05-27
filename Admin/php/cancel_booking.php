<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

// Database connection parameters
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'medstudy';

// Establish database connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    error_log("Database Connection Error: " . $conn->connect_error);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

try {
    // Start a transaction
    $conn->begin_transaction();

    // Get booking details from request
    $raw_input = file_get_contents('php://input');
    error_log("Raw input received: " . $raw_input);

    $data = json_decode($raw_input, true);
    
    // Log JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Decoding Error: " . json_last_error_msg());
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    // Validate input
    if (!isset($data['booking_id'])) {
        error_log("Input data: " . print_r($data, true));
        throw new Exception('Missing booking ID');
    }

    $booking_id = $conn->real_escape_string($data['booking_id']);
    $cancellation_reason = $conn->real_escape_string($data['cancel_reason'] ?? 'No reason provided');

    // Fetch booking details before update
    $fetch_query = "SELECT * FROM booking WHERE book_id = ?";
    $fetch_stmt = $conn->prepare($fetch_query);
    $fetch_stmt->bind_param("i", $booking_id);
    $fetch_stmt->execute();
    $booking_result = $fetch_stmt->get_result();

    if ($booking_result->num_rows === 0) {
        throw new Exception('Booking not found');
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
        throw new Exception('Failed to log cancellation');
    }
    $log_stmt->close();

    // Update booking status to cancelled by admin
    $update_query = "UPDATE booking SET status = 'cancelled by admin' WHERE book_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $booking_id);
    $update_stmt->execute();

    if ($update_stmt->affected_rows === 0) {
        throw new Exception('Failed to update booking status');
    }
    $update_stmt->close();

    // Commit transaction
    $conn->commit();

    // Log successful cancellation
    error_log("Booking {$booking_id} cancelled and logged successfully");

    echo json_encode([
        'success' => true, 
        'message' => 'Booking cancelled successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Log error details
    error_log('Booking Cancellation Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>