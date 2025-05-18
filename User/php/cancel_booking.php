<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['room_id']) || !isset($data['booking_date']) || 
    !isset($data['start_time']) || !isset($data['end_time'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
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

// Delete the booking
$stmt = $conn->prepare("DELETE FROM booking 
                       WHERE user_id = ? 
                       AND room_id = ? 
                       AND booking_date = ? 
                       AND start_time = ? 
                       AND end_time = ?");

$stmt->bind_param("iisss", 
    $user_id, 
    $data['room_id'], 
    $data['booking_date'], 
    $data['start_time'], 
    $data['end_time']
);

$success = $stmt->execute();

if ($success && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking or booking not found']);
}

$stmt->close();
$conn->close(); 