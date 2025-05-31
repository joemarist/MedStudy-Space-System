<?php
// Removed debug logging
// Removed debug logging
// Removed debug logging

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // Removed debug logging
    die("Connection failed: " . $conn->connect_error);
}

// Removed debug logging
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Removed debug logging
    die("Invalid request method");
}

$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

if ($room_id <= 0) {
    // Removed debug logging
    die("Invalid room ID");
}

// Check if room exists and has no active bookings
$stmt = $conn->prepare("SELECT room_id FROM rooms WHERE room_id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Removed debug logging
    die("Room not found");
}

// Check for active bookings
$booking_check = $conn->prepare("SELECT COUNT(*) as booking_count FROM booking WHERE room_id = ? AND status IN ('in_process', 'completed')");
$booking_check->bind_param("i", $room_id);
$booking_check->execute();
$booking_result = $booking_check->get_result()->fetch_assoc();

if ($booking_result['booking_count'] > 0) {
    die("Cannot delete room with active bookings");
}

// Prepare delete statement
$delete_stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
$delete_stmt->bind_param("i", $room_id);

// Removed debug logging
$delete_stmt->execute();

if ($delete_stmt->affected_rows > 0) {
    // Removed debug logging
    echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
} else {
    // Removed debug logging
    echo json_encode(['success' => false, 'message' => 'Failed to delete room']);
}

$delete_stmt->close();
$conn->close();
?>