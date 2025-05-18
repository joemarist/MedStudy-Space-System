<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set timezone to Asia/Manila
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Get room ID and date range from request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['room_id'], $data['start_date'], $data['end_date'])) {
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

$room_id = $conn->real_escape_string($data['room_id']);
$start_date = $conn->real_escape_string($data['start_date']);
$end_date = $conn->real_escape_string($data['end_date']);
$user_id = $_SESSION['user_id'] ?? null;

// Get all bookings for the room within date range
$query = "SELECT booking_date, 
          GROUP_CONCAT(DISTINCT CASE 
              WHEN user_id = ? THEN 'user'
              ELSE 'others' 
          END) as booking_type,
          COUNT(*) as booking_count,
          GROUP_CONCAT(start_time) as start_times,
          GROUP_CONCAT(end_time) as end_times
          FROM booking 
          WHERE room_id = ? 
          AND booking_date BETWEEN ? AND ?
          GROUP BY booking_date";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiss", $user_id, $room_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['booking_date'];
    $booking_type = $row['booking_type'];
    $times = array_map(function($start, $end) {
        return ['start' => $start, 'end' => $end];
    }, explode(',', $row['start_times']), explode(',', $row['end_times']));

    // Determine the status for the calendar
    $status = 'vacant';
    if (strpos($booking_type, 'user') !== false) {
        $status = 'user-booking';
    } else if ($row['booking_count'] >= 8) { // Assuming 8 slots per day (9 hours / minimum 1 hour booking)
        $status = 'fully-booked';
    } else {
        $status = 'partially-booked';
    }

    $bookings[] = [
        'date' => $date,
        'status' => $status,
        'times' => $times
    ];
}

echo json_encode(['success' => true, 'bookings' => $bookings]);

$stmt->close();
$conn->close();
?> 