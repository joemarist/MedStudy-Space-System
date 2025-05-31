<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', '/MedStudy-Space-System/php_errors.log');

if (!isset($_SESSION['email'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    $error = "Database connection failed: " . $conn->connect_error;
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $error]);
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
    $error = "User not found for email: $email";
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $error]);
    exit();
}
$user_id = $user['user_id'];

// Get form data
$first_name = $_POST['first_name'] ?? '';
$middle_name = $_POST['middle_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$contact_number = $_POST['contact_number'] ?? '';

$profile_pic_blob = null;
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_pic'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 2 * 1024 * 1024; // 2MB
    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
        $profile_pic_blob = file_get_contents($file['tmp_name']);
    } else {
        $error = "Invalid file type (" . $file['type'] . ") or size (" . $file['size'] . ")";
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error]);
        exit();
    }
} else {
    $error_code = $_FILES['profile_pic']['error'] ?? 'No file';
}

// Update user_details
$sql = "UPDATE user_details SET first_name = ?, middle_name = ?, last_name = ?, contact_number = ?";
$params = [$first_name, $middle_name, $last_name, $contact_number];
$types = "ssss";
if ($profile_pic_blob !== null) {
    $sql .= ", profile_pic = ?";
    $params[] = $profile_pic_blob;
    $types .= "s";
}
$sql .= " WHERE user_id = ?";
$params[] = $user_id;
$types .= "i";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $error = "Prepare failed: " . $conn->error;
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $error]);
    exit();
}
$stmt->bind_param($types, ...$params);
if ($stmt->execute()) {
    $_SESSION['first_name'] = $first_name;
    $_SESSION['middle_name'] = $middle_name;
    $_SESSION['last_name'] = $last_name;
    if ($profile_pic_blob !== null) {
        $_SESSION['profile_pic'] = 'data:image/jpeg;base64,' . base64_encode($profile_pic_blob);
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    $error = "Execute failed: " . $stmt->error;
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $error]);
}
$stmt->close();
$conn->close();
?>