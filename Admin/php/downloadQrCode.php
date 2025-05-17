<?php
// Database config
$host = 'localhost';
$dbname = 'medstudy';
$username = 'root';
$password = ''; // your password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database connection failed: " . $e->getMessage();
    exit;
}

// Check if room_id is provided
if (!isset($_GET['room_id'])) {
    http_response_code(400);
    echo "Room ID is required";
    exit;
}

$room_id = (int)$_GET['room_id'];

try {
    // Get room details including QR code and room key
    $stmt = $pdo->prepare("SELECT room_key, qr_code FROM rooms WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        http_response_code(404);
        echo "Room not found";
        exit;
    }

    // Set headers for PNG image download
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="room_' . $room['room_key'] . '_qr.png"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output the QR code image
    echo $room['qr_code'];
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
    exit;
}
?>