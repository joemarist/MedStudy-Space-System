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
$query = "SELECT 
          booking_date, 
          status,
          room_id
          FROM booking 
          WHERE room_id = ? 
          AND user_id = ?
          AND booking_date BETWEEN ? AND ?
          AND status NOT IN ('completed', 'no_show')";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiss", $room_id, $user_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['booking_date'];
    
    // Determine booking status for display
    $status = 'user-booking';
    if ($row['status'] === 'cancelled by admin' || $row['status'] === 'cancelled by student') {
        $status = 'cancelled';
    }

    // Ensure unique entries and include room_id
    $bookings[] = [
        'date' => $date,
        'status' => $status,
        'room_id' => $row['room_id']
    ];
}

echo json_encode([
    'success' => true, 
    'bookings' => array_values($bookings)
]);

$stmt->close();
$conn->close();
?> 