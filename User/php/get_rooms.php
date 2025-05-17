<?php
// Database connection
$host = 'localhost';
$dbname = 'medstudy';
$username = 'root';
$password = '';

try {
    // Create PDO connection with error handling
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Debug: Log the query we're about to execute
    $query = "SELECT * FROM rooms ORDER BY room_id DESC";
    error_log("Executing query: " . $query);

    // Execute query and fetch results
    $stmt = $pdo->query($query);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log raw results immediately after fetch
    error_log("=== Initial Raw Results ===");
    error_log("Number of rooms fetched: " . count($rooms));
    foreach ($rooms as $index => $room) {
        error_log("Index $index - Room ID: {$room['room_id']}, Name: {$room['room_name']}");
    }

    // Create a new array for processed rooms
    $processed_rooms = [];
    
    // Process each room
    foreach ($rooms as $room) {
        $processed_room = $room; // Create a copy of the room data
        
        // Convert image to base64 if exists
        if (!empty($processed_room['room_image'])) {
            $processed_room['room_image_base64'] = base64_encode($processed_room['room_image']);
        }
        unset($processed_room['room_image']); // Remove binary data
        
        // Validate fields
        $required_fields = ['room_id', 'room_name', 'student_capacity', 'chairs', 'tables', 'status'];
        foreach ($required_fields as $field) {
            if (empty($processed_room[$field])) {
                error_log("Warning: Empty $field for room {$processed_room['room_id']}");
                $processed_room[$field] = 'N/A';
            }
        }
        
        // Add processed room to new array
        $processed_rooms[] = $processed_room;
    }
    
    // Replace original array with processed array
    $rooms = $processed_rooms;
    
    // Debug: Log final processed data
    error_log("=== Final Processed Data ===");
    error_log("Number of processed rooms: " . count($rooms));
    foreach ($rooms as $index => $room) {
        error_log(sprintf(
            "Index %d - Room ID: %s, Name: %s, Status: %s",
            $index,
            $room['room_id'],
            $room['room_name'],
            $room['status']
        ));
    }

} catch (PDOException $e) {
    error_log("Database error in get_rooms.php: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());
    $rooms = [];
} catch (Exception $e) {
    error_log("General error in get_rooms.php: " . $e->getMessage());
    $rooms = [];
}

// Final verification of rooms array
if (!isset($rooms) || !is_array($rooms)) {
    error_log("Initializing empty rooms array");
    $rooms = [];
}

// Debug: Final array check
error_log("=== Final Array Check ===");
error_log("Final room count: " . count($rooms));
foreach ($rooms as $index => $room) {
    error_log("Final Index $index - Room ID: {$room['room_id']}");
}
?> 