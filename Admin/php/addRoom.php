<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

require_once __DIR__ . '/../../vendor/autoload.php';
use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Common\EccLevel;

// Database config
$host = 'localhost';
$dbname = 'medstudy';
$username = 'root';
$password = '';

// Log POST and FILES data for debugging
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Validate required POST data
if (empty($_POST['room_name']) ||
    !isset($_POST['student_capacity']) ||
    !isset($_POST['chairs']) ||
    !isset($_POST['tables']) ||
    empty($_POST['status'])) {
    error_log("Missing required room data");
    http_response_code(400);
    echo "Missing required room data";
    exit;
}

// Validate and sanitize inputs
$room_name = trim($_POST['room_name']);
$student_capacity = (int)$_POST['student_capacity'];
$chairs = (int)$_POST['chairs'];
$tables = (int)$_POST['tables'];
$status = trim($_POST['status']);

// Validate uploaded room image
if (!isset($_FILES['room_image']) || $_FILES['room_image']['error'] !== UPLOAD_ERR_OK) {
    error_log("Room image upload failed or missing");
    http_response_code(400);
    echo "Room image upload failed or missing";
    exit;
}

try {
    // Read room image
    $room_image_blob = file_get_contents($_FILES['room_image']['tmp_name']);
    if ($room_image_blob === false) {
        throw new Exception("Failed to read room image file");
    }

    // Generate room key
    $room_key = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);

    // Generate QR code
    $qrOptions = new QROptions([
        'eccLevel' => EccLevel::L,
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'imageBase64' => false,
        'scale' => 5
    ]);

    $qrcode = new QRCode($qrOptions);
    $qr_code_blob = $qrcode->render($room_key);

    if (empty($qr_code_blob)) {
        throw new Exception("QR code generation failed");
    }

    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Start transaction
    $pdo->beginTransaction();

    // Insert room
    $sql = "INSERT INTO rooms (room_name, student_capacity, chairs, tables, status, room_key, qr_code, room_image) 
            VALUES (:room_name, :student_capacity, :chairs, :tables, :status, :room_key, :qr_code, :room_image)";
    
    $stmt = $pdo->prepare($sql);
    
    $result = $stmt->execute([
        ':room_name' => $room_name,
        ':student_capacity' => $student_capacity,
        ':chairs' => $chairs,
        ':tables' => $tables,
        ':status' => $status,
        ':room_key' => $room_key,
        ':qr_code' => $qr_code_blob,
        ':room_image' => $room_image_blob
    ]);

    if (!$result) {
        throw new Exception("Failed to insert room data");
    }

    $newId = $pdo->lastInsertId();
    if (!$newId) {
        throw new Exception("Failed to get new room ID");
    }

    // Commit transaction
    $pdo->commit();

    error_log("Room successfully added with ID: " . $newId);
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Room successfully added', 'room_id' => $newId]);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
