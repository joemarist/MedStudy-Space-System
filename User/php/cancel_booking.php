<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php-error.log');

session_start();
header('Content-Type: application/json; charset=utf-8');

// Detailed logging function with more verbose output
function detailed_log($message, $data = null) {
    $log_message = "[CANCEL BOOKING] " . $message;
    if ($data !== null) {
        $log_message .= " | " . (is_array($data) ? json_encode($data) : $data);
    }
    
    error_log($log_message);
    file_put_contents('cancel_booking_debug.log', 
        date('[Y-m-d H:i:s] ') . $log_message . "\n", 
        FILE_APPEND
    );
}

// Log all incoming POST data
detailed_log("Incoming POST Data", $_POST);
detailed_log("Incoming FILES Data", $_FILES);

// Check authentication
if (!isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
    detailed_log("Authentication Failed", [
        'email_set' => isset($_SESSION['email']),
        'user_id_set' => isset($_SESSION['user_id'])
    ]);
    echo json_encode([
        'success' => false, 
        'message' => 'Not authenticated. Please log in again.',
        'debug' => [
            'email_set' => isset($_SESSION['email']),
            'user_id_set' => isset($_SESSION['user_id'])
        ]
    ]);
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    detailed_log("Database Connection Failed", $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
    exit();
}

// Get booking ID from POST request with multiple fallback methods
$book_id = null;
if (isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
} elseif (isset($_REQUEST['book_id'])) {
    $book_id = $_REQUEST['book_id'];
}

detailed_log("Received Book ID", [
    'from_post' => $_POST['book_id'] ?? 'NOT SET',
    'from_request' => $_REQUEST['book_id'] ?? 'NOT SET',
    'final_book_id' => $book_id
]);

if (!$book_id) {
    detailed_log("No Booking ID Provided", [
        'post_data' => $_POST,
        'request_data' => $_REQUEST
    ]);
    echo json_encode([
        'success' => false, 
        'message' => 'No booking ID provided. Please select a booking to cancel.',
        'debug' => [
            'post_data' => $_POST,
            'request_data' => $_REQUEST
        ]
    ]);
    exit();
}

// Ensure book_id is an integer
$book_id = intval($book_id);

// Get user ID from session
$user_id = $_SESSION['user_id'];
detailed_log("User ID from Session", $user_id);

// Comprehensive booking verification query
$check_stmt = $conn->prepare("
    SELECT b.book_id, b.user_id, b.status, b.booking_date, 
           r.room_name, u.email
    FROM booking b
    JOIN user_accounts u ON b.user_id = u.user_id
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.book_id = ?
");
$check_stmt->bind_param("i", $book_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    detailed_log("No Booking Found", [
        'book_id' => $book_id,
        'user_id' => $user_id
    ]);
    echo json_encode([
        'success' => false, 
        'message' => 'Booking not found in the system.',
        'debug' => [
            'book_id' => $book_id,
            'user_id' => $user_id
        ]
    ]);
    $check_stmt->close();
    $conn->close();
    exit();
}

$booking = $check_result->fetch_assoc();
$check_stmt->close();

// Detailed logging of booking details
detailed_log("Booking Details", [
    'book_id' => $booking['book_id'],
    'db_user_id' => $booking['user_id'],
    'session_user_id' => $user_id,
    'status' => $booking['status'],
    'booking_date' => $booking['booking_date'],
    'room_name' => $booking['room_name'],
    'user_email' => $booking['email']
]);

// Strict user ID comparison with type-safe comparison
if ((int)$booking['user_id'] !== (int)$user_id) {
    detailed_log("User ID Mismatch", [
        'db_user_id' => $booking['user_id'],
        'session_user_id' => $user_id,
        'db_user_id_type' => gettype($booking['user_id']),
        'session_user_id_type' => gettype($user_id)
    ]);
    echo json_encode([
        'success' => false, 
        'message' => 'Booking not found or does not belong to you.',
        'debug' => [
            'db_user_id' => $booking['user_id'],
            'session_user_id' => $user_id
        ]
    ]);
    $conn->close();
    exit();
}

// Check booking status
if (in_array($booking['status'], ['cancelled by admin', 'cancelled by student'])) {
    detailed_log("Booking Already Cancelled", [
        'status' => $booking['status']
    ]);
    echo json_encode([
        'success' => false, 
        'message' => 'This booking has already been cancelled.'
    ]);
    $conn->close();
    exit();
}

// Check booking date
$booking_date = new DateTime($booking['booking_date']);
$current_date = new DateTime();

if ($booking_date < $current_date) {
    detailed_log("Cannot Cancel Past Booking", [
        'booking_date' => $booking['booking_date']
    ]);
    echo json_encode([
        'success' => false, 
        'message' => 'Cannot cancel past bookings.'
    ]);
    $conn->close();
    exit();
}

// Prepare and execute update query
$stmt = $conn->prepare("
    UPDATE booking 
    SET status = 'cancelled by student' 
    WHERE book_id = ? 
    AND user_id = ?
    AND status NOT IN ('cancelled by admin', 'cancelled by student')
");
$stmt->bind_param("ii", $book_id, $user_id);
$result = $stmt->execute();

// Ensure clean JSON response
$response = [
    'success' => false,
    'message' => 'Unable to cancel booking. Please try again or contact support.'
];

if ($result && $stmt->affected_rows > 0) {
    // Update response for successful cancellation
    $response = [
        'success' => true,
        'message' => 'Booking cancelled successfully',
        'book_id' => $book_id
    ];

    detailed_log("Booking Cancelled Successfully", [
        'book_id' => $book_id
    ]);
} else {
    detailed_log("Unable to Cancel Booking", [
        'affected_rows' => $stmt->affected_rows,
        'error' => $stmt->error
    ]);
}

// Ensure JSON headers and clean output
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_PRETTY_PRINT);

$stmt->close();
$conn->close();
exit();
?> 