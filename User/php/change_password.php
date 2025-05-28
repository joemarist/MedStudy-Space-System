<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'User not logged in'
    ]);
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed'
    ]);
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get new password from POST
$newPassword = $_POST['newPassword'] ?? '';

// Validate new password
if (empty($newPassword)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please enter a new password'
    ]);
    exit();
}

if (strlen($newPassword) < 8) {
    echo json_encode([
        'success' => false, 
        'message' => 'Password must be at least 8 characters long'
    ]);
    exit();
}

// Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password in database
$stmt = $conn->prepare("UPDATE user_accounts SET password = ?, is_temp_password = 0, original_password = ? WHERE user_id = ?");
$stmt->bind_param("ssi", $hashedPassword, $newPassword, $_SESSION['user_id']);

if ($stmt->execute()) {
    // Log successful password change
    file_put_contents("../../debug_log.txt", 
        date('[Y-m-d H:i:s] ') . 
        "Password changed for user ID: {$_SESSION['user_id']}\n", 
        FILE_APPEND
    );

    echo json_encode([
        'success' => true, 
        'message' => 'Password changed successfully'
    ]);
} else {
    // Log failed password change
    file_put_contents("../../debug_log.txt", 
        date('[Y-m-d H:i:s] ') . 
        "Failed to change password for user ID: {$_SESSION['user_id']}\n", 
        FILE_APPEND
    );

    echo json_encode([
        'success' => false, 
        'message' => 'Failed to change password'
    ]);
}

$stmt->close();
$conn->close();
exit();
?> 