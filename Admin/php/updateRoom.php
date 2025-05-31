<?php
// Prevent any unwanted output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

// Set JSON content type
header('Content-Type: application/json');

// Function to clean output buffer and send JSON response
function sendJsonResponse($success, $message, $data = null) {
    // Clean any output that might have been generated
    if (ob_get_length()) ob_clean();
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    // Database connection
    $conn = new mysqli("localhost", "root", "", "medstudy");
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Validate method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Parse inputs
    $room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
    $room_name = isset($_POST['room_name']) ? trim($_POST['room_name']) : '';
    $student_capacity = isset($_POST['student_capacity']) ? (int)$_POST['student_capacity'] : 0;
    $chairs = isset($_POST['chairs']) ? (int)$_POST['chairs'] : 0;
    $tables = isset($_POST['tables']) ? (int)$_POST['tables'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'Available';

    // Minimal validation
    if ($room_id <= 0) {
        throw new Exception('Invalid room ID: ' . $room_id);
    }

    if (!$room_name) {
        throw new Exception('Room name is required');
    }

    if ($student_capacity <= 0 || $chairs <= 0 || $tables <= 0) {
        throw new Exception('Student capacity, chairs, and tables must be positive numbers');
    }

    // Verify room exists
    $verify_stmt = $conn->prepare("SELECT room_id FROM rooms WHERE room_id = ?");
    if (!$verify_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $verify_stmt->bind_param("i", $room_id);
    if (!$verify_stmt->execute()) {
        throw new Exception('Database error: ' . $verify_stmt->error);
    }

    $verify_result = $verify_stmt->get_result();
    if ($verify_result->num_rows === 0) {
        throw new Exception('Room not found (ID: ' . $room_id . ')');
    }
    $verify_stmt->close();

    // Handle image
    $room_image_blob = NULL;
    if (isset($_FILES['room_image']) && is_array($_FILES['room_image'])) {
        if ($_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
            if (!empty($_FILES['room_image']['tmp_name'])) {
                // Validate file size (max 5MB)
                if ($_FILES['room_image']['size'] > 5 * 1024 * 1024) {
                    throw new Exception('Image file is too large. Maximum size is 5MB.');
                }

                $image_type = mime_content_type($_FILES['room_image']['tmp_name']);
                
                if (!in_array($image_type, ['image/png', 'image/jpeg', 'image/jpg'])) {
                    throw new Exception('Invalid image type. Please upload PNG or JPEG.');
                }

                // Create image from uploaded file
                $source = ($image_type === 'image/png') ? 
                    imagecreatefrompng($_FILES['room_image']['tmp_name']) : 
                    imagecreatefromjpeg($_FILES['room_image']['tmp_name']);

                if (!$source) {
                    throw new Exception('Failed to process image file.');
                }

                // Process image
                $width = imagesx($source);
                $height = imagesy($source);
                $max_width = 800;
                
                if ($width > $max_width) {
                    $new_width = $max_width;
                    $new_height = floor($height * ($max_width / $width));
                } else {
                    $new_width = $width;
                    $new_height = $height;
                }

                $new_image = imagecreatetruecolor($new_width, $new_height);

                // Handle transparency for PNG
                if ($image_type === 'image/png') {
                    imagealphablending($new_image, false);
                    imagesavealpha($new_image, true);
                    $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                    imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
                }

                imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                ob_start();
                if ($image_type === 'image/png') {
                    imagepng($new_image, null, 9);
                } else {
                    imagejpeg($new_image, null, 90);
                }
                $room_image_blob = ob_get_clean();

                imagedestroy($source);
                imagedestroy($new_image);
            }
        } else if ($_FILES['room_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            throw new Exception('File upload error: ' . 
                ($error_messages[$_FILES['room_image']['error']] ?? 'Unknown upload error'));
        }
    }

    // Update query
    $sql = "UPDATE rooms SET room_name = ?, student_capacity = ?, chairs = ?, tables = ?, status = ?";
    $params = [$room_name, $student_capacity, $chairs, $tables, $status];
    $types = "siiis";

    if ($room_image_blob !== NULL) {
        $sql .= ", room_image = ?";
        $params[] = $room_image_blob;
        $types .= "s";
    }

    $sql .= " WHERE room_id = ?";
    $params[] = $room_id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update room: ' . $stmt->error);
    }

    if ($stmt->affected_rows > 0) {
        sendJsonResponse(true, 'Room updated successfully');
    } else {
        sendJsonResponse(true, 'No changes were made to the room');
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    sendJsonResponse(false, $e->getMessage());
}
?>