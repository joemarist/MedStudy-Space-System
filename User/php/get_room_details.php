<?php
session_start();
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

// Get room ID from request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['room_id'])) {
    die(json_encode(['success' => false, 'message' => 'Missing room_id']));
}

$room_id = $conn->real_escape_string($data['room_id']);

// Fetch room details
$query = "SELECT room_name, room_image, status, capacity, chairs, tables 
          FROM rooms 
          WHERE room_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $room = $result->fetch_assoc();
    echo json_encode([
        'success' => true, 
        'room' => [
            'name' => $room['room_name'],
            'image' => $room['room_image'],
            'status' => $room['status'],
            'capacity' => $room['capacity'],
            'chairs' => $room['chairs'],
            'tables' => $room['tables']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
}

$stmt->close();
$conn->close();
?> 