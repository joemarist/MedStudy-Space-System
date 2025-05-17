<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'medstudy';
$username = 'root';
$password = '';

$rooms = [];

try {
    // Create PDO connection with error handling
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_FOUND_ROWS => true
    ]);

    // First, get all room IDs to verify what we have
    $idQuery = $pdo->query("SELECT room_id FROM rooms ORDER BY room_id DESC");
    $allIds = $idQuery->fetchAll(PDO::FETCH_COLUMN);
    error_log("Found room IDs: " . implode(", ", $allIds));

    // Now fetch complete room data
    $stmt = $pdo->prepare("
        SELECT 
            room_id,
            room_name,
            student_capacity,
            chairs,
            tables,
            status,
            room_key,
            qr_code,
            room_image
        FROM rooms 
        WHERE room_id = ?
    ");

    // Process each room individually to avoid memory issues with BLOBs
    foreach ($allIds as $roomId) {
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room) {
            error_log("Processing room ID: " . $roomId);
            
            // Handle BLOB data carefully
            if (!empty($room['qr_code'])) {
                $room['qr_code_base64'] = base64_encode($room['qr_code']);
                unset($room['qr_code']);
            }
            
            if (!empty($room['room_image'])) {
                $room['room_image_base64'] = base64_encode($room['room_image']);
                unset($room['room_image']);
            }
            
            $rooms[] = $room;
            error_log("Successfully processed room: {$room['room_name']} (ID: {$room['room_id']})");
        } else {
            error_log("Warning: Could not fetch data for room ID: " . $roomId);
        }
    }

    // Verify final array
    error_log("Total rooms processed: " . count($rooms));
    foreach ($rooms as $index => $room) {
        error_log("Room {$index}: ID={$room['room_id']}, Name={$room['room_name']}, Key={$room['room_key']}");
    }

} catch (Exception $e) {
    error_log("Database error in rooms_connect.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $rooms = [];
}

// Make sure rooms array is available even if empty
if (!isset($rooms)) {
    $rooms = [];
}

// Debug output final state
error_log("=== FINAL STATE ===");
error_log("Total rooms in array: " . count($rooms));
foreach ($rooms as $room) {
    error_log("Final room data - ID: {$room['room_id']}, Name: {$room['room_name']}, Key: {$room['room_key']}");
}
?>