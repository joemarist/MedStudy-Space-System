<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Get user_id from email
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT user_id FROM user_accounts WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}
$user_id = $user['user_id'];

// Get form data
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$contact_number = $_POST['contact_number'] ?? '';

$profile_pic_blob = null;
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_pic'];
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB
    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
        $profile_pic_blob = file_get_contents($file['tmp_name']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid file type or size']);
        exit();
    }
}

// Update user_details
$stmt = $conn->prepare("UPDATE user_details SET first_name = ?, last_name = ?, contact_number = ?, profile_pic = COALESCE(?, profile_pic) WHERE user_id = ?");
$null = null;
$stmt->bind_param("ssssi", $first_name, $last_name, $contact_number, $null, $user_id);
if ($profile_pic_blob) {
    $stmt->send_long_data(3, $profile_pic_blob);
}
if ($stmt->execute()) {
    // Update session variables
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    $_SESSION['profile_pic'] = $profile_pic_blob ? 'data:image/jpeg;base64,' . base64_encode($profile_pic_blob) : $_SESSION['profile_pic'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
?>