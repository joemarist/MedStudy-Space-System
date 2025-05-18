<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get user ID
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT user_id FROM user_accounts WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_id = $user['user_id'];
$start_date = $_POST['start_date'] ?? date('Y-m-d');
$end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days'));

// Get user's bookings
$bookings_query = "
    SELECT b.booking_date, b.start_time, b.end_time, 
           r.room_name, r.room_id, r.student_capacity, r.chairs, r.tables,
           r.qr_code, r.room_image
    FROM booking b
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.user_id = ? AND b.booking_date BETWEEN ? AND ?
    ORDER BY b.booking_date, b.start_time";

$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$user_bookings_result = $stmt->get_result();
$user_bookings = [];

while ($row = $user_bookings_result->fetch_assoc()) {
    $date = $row['booking_date'];
    if (!isset($user_bookings[$date])) {
        $user_bookings[$date] = [];
    }
    
    // Convert binary data to base64 for images
    $room_image = $row['room_image'] ? 'data:image/jpeg;base64,' . base64_encode($row['room_image']) : null;
    $qr_code = $row['qr_code'] ? 'data:image/png;base64,' . base64_encode($row['qr_code']) : null;
    
    $user_bookings[$date][] = [
        'room_name' => $row['room_name'],
        'room_id' => $row['room_id'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'student_capacity' => $row['student_capacity'],
        'chairs' => $row['chairs'],
        'tables' => $row['tables'],
        'room_image' => $room_image,
        'qr_code' => $qr_code
    ];
}
$stmt->close();

// Get room availability
$availability_query = "
    SELECT 
        b.booking_date,
        COUNT(DISTINCT r.room_id) as total_rooms,
        COUNT(DISTINCT b.room_id) as booked_rooms
    FROM rooms r
    LEFT JOIN booking b ON r.room_id = b.room_id 
        AND b.booking_date BETWEEN ? AND ?
    GROUP BY b.booking_date";

$stmt = $conn->prepare($availability_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$availability_result = $stmt->get_result();
$room_availability = [];

while ($row = $availability_result->fetch_assoc()) {
    $date = $row['booking_date'];
    $room_availability[$date] = [
        'total_rooms' => $row['total_rooms'],
        'booked_rooms' => $row['booked_rooms'],
        'status' => $row['booked_rooms'] >= $row['total_rooms'] ? 'fully_booked' : 'available'
    ];
}
$stmt->close();

$conn->close();

echo json_encode([
    'success' => true,
    'user_bookings' => $user_bookings,
    'room_availability' => $room_availability
]); 