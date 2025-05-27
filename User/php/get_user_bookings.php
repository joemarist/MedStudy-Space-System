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

// Log user details and booking retrieval parameters
error_log("User ID: $user_id");
error_log("Start Date: $start_date");
error_log("End Date: $end_date");

// Get user's bookings (excluding cancelled bookings)
$bookings_query = "
    SELECT b.booking_date, b.start_time, b.end_time, 
           r.room_name, r.room_id, r.student_capacity, r.chairs, r.tables,
           r.qr_code, r.room_image, b.book_id, b.status
    FROM booking b
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.user_id = ? AND b.booking_date BETWEEN ? AND ?
    ORDER BY b.booking_date, b.start_time";

$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$user_bookings_result = $stmt->get_result();
$user_bookings = [];

// Log total number of bookings retrieved
$total_bookings = $user_bookings_result->num_rows;
error_log("Total Bookings Retrieved: $total_bookings");

while ($row = $user_bookings_result->fetch_assoc()) {
    $date = $row['booking_date'];
    if (!isset($user_bookings[$date])) {
        $user_bookings[$date] = [];
    }
    
    // Log each booking details
    error_log("Booking Details: " . json_encode([
        'date' => $date,
        'book_id' => $row['book_id'],
        'room_name' => $row['room_name'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'status' => $row['status']
    ]));
    
    // Convert binary data to base64 for images
    $room_image = $row['room_image'] ? 'data:image/jpeg;base64,' . base64_encode($row['room_image']) : null;
    $qr_code = $row['qr_code'] ? 'data:image/png;base64,' . base64_encode($row['qr_code']) : null;
    
    $user_bookings[$date][] = [
        'book_id' => $row['book_id'],
        'room_name' => $row['room_name'],
        'room_id' => $row['room_id'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'student_capacity' => $row['student_capacity'],
        'chairs' => $row['chairs'],
        'tables' => $row['tables'],
        'room_image' => $room_image,
        'qr_code' => $qr_code,
        'status' => $row['status']
    ];
}
$stmt->close();

// Enhanced room availability query
$availability_query = "
    SELECT 
        b.booking_date,
        COUNT(DISTINCT r.room_id) as total_rooms,
        COUNT(DISTINCT CASE WHEN b.status = 'cancelled by admin' THEN b.room_id END) as admin_cancelled_rooms,
        COUNT(DISTINCT CASE WHEN b.status = 'cancelled by student' THEN b.room_id END) as student_cancelled_rooms,
        COUNT(DISTINCT CASE WHEN b.status NOT IN ('cancelled by admin', 'cancelled by student') THEN b.room_id END) as active_booked_rooms
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
        'admin_cancelled_rooms' => $row['admin_cancelled_rooms'],
        'student_cancelled_rooms' => $row['student_cancelled_rooms'],
        'active_booked_rooms' => $row['active_booked_rooms'],
        'status' => $row['admin_cancelled_rooms'] > 0 ? 'admin_cancelled' : 
                    ($row['active_booked_rooms'] > 0 ? 'booked' : 'available')
    ];
}
$stmt->close();

$conn->close();

echo json_encode([
    'success' => true,
    'user_bookings' => $user_bookings,
    'room_availability' => $room_availability
]); 