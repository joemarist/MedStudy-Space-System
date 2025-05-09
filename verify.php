<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$client_id = "80047855417-dfsmenc4jgtr2me0vm4a5tl76s91bf45.apps.googleusercontent.com";
$id_token = $_POST['credential'] ?? null;

if ($id_token) {
    $payload = json_decode(file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=$id_token"), true);
    file_put_contents("debug_log.txt", print_r($payload, true) . "\n", FILE_APPEND);

    if ($payload && isset($payload['email']) && $payload['aud'] === $client_id && $payload['iss'] === 'https://accounts.google.com') {
        $email = $payload['email'];
        $firstName = $payload['given_name'] ?? 'Firstname';
        $lastName = $payload['family_name'] ?? 'Lastname';
        $profilePicUrl = $payload['picture'] ?? null;

        $profilePicBlob = null;
        if ($profilePicUrl) {
            $profilePicBlob = @file_get_contents($profilePicUrl);
            if ($profilePicBlob === false) {
                file_put_contents("debug_log.txt", "Failed to download profile picture from: $profilePicUrl\n", FILE_APPEND);
                $profilePicBlob = null;
            } else {
                file_put_contents("debug_log.txt", "Profile picture downloaded: $profilePicUrl\n", FILE_APPEND);
            }
        }

        if (!str_ends_with($email, '@usep.edu.ph')) {
            file_put_contents("debug_log.txt", "Non-usep.edu.ph email detected: $email\n", FILE_APPEND);
            echo "<script>alert('Only @usep.edu.ph accounts are allowed.'); window.location.href='index.php?status=denied';</script>";
            exit();
        }

        $defaultPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

        $stmt = $conn->prepare("SELECT user_id FROM user_accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingUser = $result->fetch_assoc();
        $stmt->close();

        if (!$existingUser) {
            $stmt = $conn->prepare("INSERT INTO user_accounts (email, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $defaultPassword);
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                file_put_contents("debug_log.txt", "New user created: user_id=$user_id, email=$email\n", FILE_APPEND);
            } else {
                file_put_contents("debug_log.txt", "Insert into user_accounts failed: " . $stmt->error . "\n", FILE_APPEND);
                echo "<script>alert('Failed to create user account.'); window.location.href='index.php?status=error';</script>";
                exit();
            }
            $stmt->close();
        } else {
            $user_id = $existingUser['user_id'];
            file_put_contents("debug_log.txt", "Existing user found: user_id=$user_id, email=$email\n", FILE_APPEND);
        }

        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, profile_pic FROM user_details WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $detailsExist = $result->fetch_assoc();
        $stmt->close();

        if (!$detailsExist) {
            $stmt = $conn->prepare("INSERT INTO user_details (user_id, first_name, last_name, profile_pic) VALUES (?, ?, ?, ?)");
            $null = null;
            $stmt->bind_param("issb", $user_id, $firstName, $lastName, $null);
            if ($profilePicBlob) {
                $stmt->send_long_data(3, $profilePicBlob);
            }
            if ($stmt->execute()) {
                file_put_contents("debug_log.txt", "User details inserted: user_id=$user_id, first_name=$firstName, last_name=$lastName, profile_pic=" . ($profilePicBlob ? "set" : "null") . "\n", FILE_APPEND);
            } else {
                file_put_contents("debug_log.txt", "Insert into user_details failed: " . $stmt->error . "\n", FILE_APPEND);
                echo "<script>alert('Failed to save user details.'); window.location.href='index.php?status=error';</script>";
                exit();
            }
            $stmt->close();
        } else {
            $firstName = $detailsExist['first_name'];
            $lastName = $detailsExist['last_name'];
            $profilePicBlob = $detailsExist['profile_pic'];
            file_put_contents("debug_log.txt", "User details exist: user_id=$user_id, first_name=$firstName, last_name=$lastName, profile_pic=" . ($profilePicBlob ? "set" : "null") . "\n", FILE_APPEND);
        }

        session_unset();
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['profile_pic'] = $profilePicBlob ? 'data:image/jpeg;base64,' . base64_encode($profilePicBlob) : null;
        file_put_contents("debug_log.txt", "Session set: email=$email, first_name=$firstName, last_name=$lastName, profile_pic=" . ($_SESSION['profile_pic'] ? "set" : "null") . "\n", FILE_APPEND);

        header("Location: index.php?status=success");
        exit();
    } else {
        file_put_contents("debug_log.txt", "Invalid Google token or payload: " . print_r($payload, true) . "\n", FILE_APPEND);
        echo "<script>alert('Invalid Google token.'); window.location.href='index.php?status=invalid';</script>";
        exit();
    }
}

if (isset($_POST['email'], $_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password FROM user_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $userAccount = $result->fetch_assoc();
    $stmt->close();

    if ($userAccount && password_verify($password, $userAccount['password'])) {
        $user_id = $userAccount['user_id'];

        $stmt = $conn->prepare("SELECT first_name, last_name, profile_pic FROM user_details WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetails = $result->fetch_assoc();
        $stmt->close();

        session_unset();
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $userDetails['first_name'] ?? 'Firstname';
        $_SESSION['last_name'] = $userDetails['last_name'] ?? 'Lastname';
        $_SESSION['profile_pic'] = $userDetails['profile_pic'] ? 'data:image/jpeg;base64,' . base64_encode($userDetails['profile_pic']) : null;
        file_put_contents("debug_log.txt", "Manual login session set: email=$email, first_name={$_SESSION['first_name']}, last_name={$_SESSION['last_name']}, profile_pic=" . ($_SESSION['profile_pic'] ? "set" : "null") . "\n", FILE_APPEND);

        header("Location: index.php?status=success");
        exit();
    } else {
        file_put_contents("debug_log.txt", "Manual login failed: email=$email\n", FILE_APPEND);
        echo "<script>alert('Invalid email or password.'); window.location.href='index.php?status=invalid_credentials';</script>";
        exit();
    }
}

file_put_contents("debug_log.txt", "No valid POST data received\n", FILE_APPEND);
echo "<script>alert('No login data provided.'); window.location.href='index.php?status=missing';</script>";
exit();
?>