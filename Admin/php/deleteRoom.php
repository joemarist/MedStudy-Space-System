<?php
// Log request
file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] Request received\n", FILE_APPEND);
file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] POST: " . json_encode($_POST) . "\n", FILE_APPEND);

// Database connection
$conn = new mysqli("localhost", "root", "", "medstudy");
if ($conn->connect_error) {
    file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] DB error: " . $conn->connect_error . "\n", FILE_APPEND);
    echo "<script>alert('Database connection failed'); window.location.href='../html/rooms.php';</script>";
    exit;
}
file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] DB connected\n", FILE_APPEND);

// Validate method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] Invalid method\n", FILE_APPEND);
    echo "<script>alert('Invalid request method'); window.location.href='../html/rooms.php';</script>";
    exit;
}

// Parse inputs
$room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] Input: id=$room_id\n", FILE_APPEND);

// Validate
if ($room_id <= 0) {
    file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] Error: Invalid room ID\n", FILE_APPEND);
    echo "<script>alert('Invalid room ID'); window.location.href='../html/rooms.php';</script>";
    exit;
}

// Check if room exists
$stmt = $conn->prepare("SELECT room_id FROM rooms WHERE room_id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] Room not found: id=$room_id\n", FILE_APPEND);
    echo "<script>alert('Room not found'); window.location.href='../html/rooms.php';</script>";
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Delete
$stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
$stmt->bind_param("i", $room_id);
file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] Executing delete\n", FILE_APPEND);

if ($stmt->execute()) {
    file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] Success: room_id=$room_id, affected=" . $stmt->affected_rows . "\n", FILE_APPEND);
    echo "<script>alert('Room deleted successfully'); window.location.href='../html/rooms.php';</script>";
} else {
    file_put_contents("../debug_log.txt", date("Y-m-d H:i:s") . " [deleteRoom.php] Delete error: " . $stmt->error . "\n", FILE_APPEND);
    echo "<script>alert('Failed to delete room: " . addslashes($stmt->error) . "'); window.location.href='../html/rooms.php';</script>";
}
$stmt->close();
$conn->close();
?>