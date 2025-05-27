<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        'error' => true, 
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

try {
    // Comprehensive query to fetch bookings with all necessary details
    $query = "
        SELECT 
            b.book_id AS booking_id, 
            b.room_id, 
            r.room_name, 
            ud.first_name, 
            ud.middle_name, 
            ud.last_name, 
            ud.contact_number,
            ud.profile_pic,
            ua.email,
            b.booking_date, 
            b.start_time, 
            b.end_time, 
            b.status,
            b.created_at,
            CASE 
                WHEN b.status = 'in_process' THEN 'status-in_process'
                WHEN b.status = 'completed' THEN 'status-completed'
                WHEN b.status = 'cancelled' THEN 'status-cancelled'
                WHEN b.status = 'no_show' THEN 'status-no_show'
                ELSE 'status-unknown'
            END AS status_class
        FROM booking b
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN user_details ud ON b.user_id = ud.user_id
        LEFT JOIN user_accounts ua ON ud.user_id = ua.user_id
        WHERE b.status NOT IN ('cancelled', 'no_show')
        ORDER BY b.booking_date, b.start_time
    ";

    // Prepare and execute the statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute statement failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Get result failed: " . $stmt->error);
    }

    $bookings = [];
    $tableInfo = [];

    // Check table existence and record counts
    $tableChecks = [
        'booking' => "SELECT COUNT(*) as count FROM booking",
        'rooms' => "SELECT COUNT(*) as count FROM rooms",
        'user_details' => "SELECT COUNT(*) as count FROM user_details",
        'user_accounts' => "SELECT COUNT(*) as count FROM user_accounts"
    ];

    foreach ($tableChecks as $tableName => $checkQuery) {
        $checkResult = $conn->query($checkQuery);
        if ($checkResult) {
            $row = $checkResult->fetch_assoc();
            $tableInfo[$tableName] = $row['count'];
        } else {
            $tableInfo[$tableName] = 'Error checking table';
        }
    }

    // Fetch bookings
    while ($row = $result->fetch_assoc()) {
        // Process profile picture
        $profilePic = null;
        if (!empty($row['profile_pic'])) {
            // Convert BLOB to base64
            $profilePic = base64_encode($row['profile_pic']);
        }

        $bookings[] = [
            'booking_id' => $row['booking_id'],
            'room_id' => $row['room_id'],
            'room_name' => $row['room_name'] ?? 'Unknown Room',
            'first_name' => $row['first_name'] ?? 'Unknown',
            'middle_name' => $row['middle_name'] ?? '',
            'last_name' => $row['last_name'] ?? 'Unknown',
            'full_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            'contact_number' => $row['contact_number'] ?? 'N/A',
            'email' => $row['email'] ?? 'N/A',
            'booking_date' => $row['booking_date'],
            'start_time' => date('h:i A', strtotime($row['start_time'])),
            'end_time' => date('h:i A', strtotime($row['end_time'])),
            'created_at' => $row['created_at'],
            'status' => $row['status'],
            'status_class' => $row['status_class'],
            'profile_pic' => $profilePic
        ];
    }

    $stmt->close();

    // Log successful booking retrieval
    error_log('Bookings retrieved successfully: ' . count($bookings) . ' bookings found');

    echo json_encode([
        'error' => false,
        'bookings' => $bookings,
        'total_bookings' => count($bookings),
        'table_info' => $tableInfo
    ]);

} catch (Exception $e) {
    // Log the full error details
    error_log('Booking Fetch Error: ' . $e->getMessage());
    error_log('Error Trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'error' => true, 
        'message' => 'Failed to load bookings',
        'error_details' => $e->getMessage(),
        'table_info' => $tableInfo ?? []
    ]);
}

$conn->close();
?> 