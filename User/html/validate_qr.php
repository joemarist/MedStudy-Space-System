<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

// Response function
function sendResponse($valid, $message = '') {
    header('Content-Type: application/json');
    echo json_encode([
        'valid' => $valid,
        'message' => $message
    ]);
    exit();
}

// Check if user is authenticated
if (!isset($_SESSION['email'])) {
    sendResponse(false, "User not authenticated");
}

// Get raw POST data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Validate input
if (!isset($data['qr_code']) || empty($data['qr_code'])) {
    sendResponse(false, "Invalid QR Code");
}

$qr_code = trim($data['qr_code']);

try {
    // Connect to database
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Prepare statement to validate QR code
    $stmt = $conn->prepare("
        SELECT b.book_id, b.room_id, b.user_id, b.booking_date, b.start_time, b.end_time, b.status, 
               r.room_name, r.room_key
        FROM booking b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE r.qr_code = ? 
        AND b.user_id = (SELECT user_id FROM user_accounts WHERE email = ?)
        AND b.status NOT IN ('completed', 'cancelled', 'no_show')
        AND b.booking_date = CURRENT_DATE
        AND CURRENT_TIME BETWEEN b.start_time AND b.end_time
    ");

    $stmt->bind_param("ss", $qr_code, $_SESSION['email']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Valid booking found
        $booking = $result->fetch_assoc();

        // Update booking status to completed
        $updateStmt = $conn->prepare("UPDATE booking SET status = 'completed' WHERE book_id = ?");
        $updateStmt->bind_param("i", $booking['book_id']);
        $updateStmt->execute();

        sendResponse(true, "Room access granted for {$booking['room_name']}");
    } else {
        // No valid booking found
        sendResponse(false, "No active booking for this room");
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Log the error
    error_log("QR Code Validation Error: " . $e->getMessage());
    sendResponse(false, "Validation error occurred");
}
?> 