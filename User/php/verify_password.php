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

// Get current password from POST
$currentPassword = $_POST['currentPassword'] ?? '';

if (empty($currentPassword)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please enter your current password'
    ]);
    exit();
}

// Retrieve stored password for the user
$stmt = $conn->prepare("SELECT password FROM user_accounts WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Verify password
if (!$user || !password_verify($currentPassword, $user['password'])) {
    // Log failed password verification attempt
    return false;
}

// Return success response
echo json_encode([
    'success' => true, 
    'message' => 'Password verified successfully'
]);
$conn->close();
exit();
?> 