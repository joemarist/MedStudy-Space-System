<?php
session_start();

// Enable detailed error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

$response = ['success' => false, 'error' => 'Unknown error'];

try {
    // Validate user authentication
    if (!isset($_SESSION['email'])) {
        throw new Exception("User not authenticated");
    }

    // Parse incoming JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !isset($data['book_id']) || !isset($data['status'])) {
        throw new Exception("Invalid request parameters");
    }

    $book_id = intval($data['book_id']);
    $status = $data['status'];

    // Validate status
    $allowed_statuses = ['in_process', 'completed', 'cancelled', 'no_show'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception("Invalid booking status");
    }

    // Connect to database
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Prepare and execute update statement with more robust join
    $stmt = $conn->prepare("UPDATE booking b 
        JOIN user_accounts ua ON b.user_id = ua.user_id 
        SET b.status = ? 
        WHERE b.book_id = ? AND ua.email = ?");
    $stmt->bind_param("sis", $status, $book_id, $_SESSION['email']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update booking status: " . $stmt->error);
    }

    // Check if any rows were actually updated
    if ($stmt->affected_rows === 0) {
        throw new Exception("No matching booking found or unauthorized access");
    }

    $stmt->close();
    $conn->close();

    // Success response
    $response = [
        'success' => true,
        'message' => 'Booking status updated successfully'
    ];

} catch (Exception $e) {
    // Log the error
    error_log("Booking Status Update Error: " . $e->getMessage());
    
    // Set error response
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];

    // Set appropriate HTTP response code based on error type
    http_response_code(
        strpos($e->getMessage(), 'authenticated') !== false ? 403 :
        (strpos($e->getMessage(), 'Invalid') !== false ? 400 : 500)
    );
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();
?> 