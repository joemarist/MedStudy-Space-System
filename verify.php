<?php
session_start();

// DB Connection
$host = "localhost";
$user = "root"; // Replace with secure user in production
$pass = ""; // Replace with secure password in production
$db = "medstudy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Google Sign-In
$client_id = "80047855417-dfsmenc4jgtr2me0vm4a5tl76s91bf45.apps.googleusercontent.com";
$id_token = $_POST['credential'] ?? null;

if ($id_token) {
    // Decode the Google ID token
    $payload = json_decode(file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=$id_token"), true);
    
    // Debugging: Log payload to file
    file_put_contents("debug_log.txt", print_r($payload, true) . "\n", FILE_APPEND);

    if ($payload && isset($payload['email']) && $payload['aud'] === $client_id && $payload['iss'] === 'https://accounts.google.com') {
        $email = $payload['email'];
        $firstName = $payload['given_name'] ?? 'Firstname';
        $lastName = $payload['family_name'] ?? 'Lastname';
        $profilePicUrl = $payload['picture'] ?? null;

        // Debugging: Log extracted data
        file_put_contents("debug_log.txt", "Email: $email, FirstName: $firstName, LastName: $lastName, ProfilePic: $profilePicUrl\n", FILE_APPEND);

        // Restrict to @usep.edu.ph emails
        if (!str_ends_with($email, '@usep.edu.ph')) {
            file_put_contents("debug_log.txt", "Non-usep.edu.ph email detected: $email\n", FILE_APPEND);
            echo "<script>alert('Only @usep.edu.ph accounts are allowed.'); window.location.href='index.php?status=denied';</script>";
            exit();
        }

        // Generate secure password
        $defaultPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

        // Check if user exists in user_accounts
        $stmt = $conn->prepare("SELECT user_id FROM user_accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingUser = $result->fetch_assoc();
        $stmt->close();

        if (!$existingUser) {
            // Insert new user into user_accounts
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

        // Check if user_details exist
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, profile_pic FROM user_details WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $detailsExist = $result->fetch_assoc();
        $stmt->close();

        if (!$detailsExist) {
            // Insert into user_details
            $stmt = $conn->prepare("INSERT INTO user_details (user_id, first_name, last_name, profile_pic) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $firstName, $lastName, $profilePicUrl);
            if ($stmt->execute()) {
                file_put_contents("debug_log.txt", "User details inserted: user_id=$user_id, first_name=$firstName, last_name=$lastName, profile_pic=$profilePicUrl\n", FILE_APPEND);
            } else {
                file_put_contents("debug_log.txt", "Insert into user_details failed: " . $stmt->error . "\n", FILE_APPEND);
                echo "<script>alert('Failed to save user details.'); window.location.href='index.php?status=error';</script>";
                exit();
            }
            $stmt->close();
        } else {
            // Update session with existing details
            $firstName = $detailsExist['first_name'];
            $lastName = $detailsExist['last_name'];
            $profilePicUrl = $detailsExist['profile_pic'];
            file_put_contents("debug_log.txt", "User details exist: user_id=$user_id, first_name=$firstName, last_name=$lastName, profile_pic=$profilePicUrl\n", FILE_APPEND);
        }

        // Clear old session data
        session_unset();
        // Store session variables
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['profile_pic'] = $profilePicUrl;
        file_put_contents("debug_log.txt", "Session set: email=$email, first_name=$firstName, last_name=$lastName, profile_pic=$profilePicUrl\n", FILE_APPEND);

        header("Location: index.php?status=success");
        exit();
    } else {
        file_put_contents("debug_log.txt", "Invalid Google token or payload: " . print_r($payload, true) . "\n", FILE_APPEND);
        echo "<script>alert('Invalid Google token.'); window.location.href='index.php?status=invalid';</script>";
        exit();
    }
}

// Manual login
if (isset($_POST['email'], $_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate credentials
    $stmt = $conn->prepare("SELECT user_id, password FROM user_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $userAccount = $result->fetch_assoc();
    $stmt->close();

    if ($userAccount && password_verify($password, $userAccount['password'])) {
        $user_id = $userAccount['user_id'];

        // Retrieve user details
        $stmt = $conn->prepare("SELECT first_name, last_name, profile_pic FROM user_details WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetails = $result->fetch_assoc();
        $stmt->close();

        // Clear old session data
        session_unset();
        // Store session variables
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $userDetails['first_name'] ?? 'Firstname';
        $_SESSION['last_name'] = $userDetails['last_name'] ?? 'Lastname';
        $_SESSION['profile_pic'] = $userDetails['profile_pic'] ?? null;
        file_put_contents("debug_log.txt", "Manual login session set: email=$email, first_name={$_SESSION['first_name']}, last_name={$_SESSION['last_name']}, profile_pic={$_SESSION['profile_pic']}\n", FILE_APPEND);

        header("Location: index.php?status=success");
        exit();
    } else {
        file_put_contents("debug_log.txt", "Manual login failed: email=$email\n", FILE_APPEND);
        echo "<script>alert('Invalid email or password.'); window.location.href='index.php?status=invalid_credentials';</script>";
        exit();
    }
}

// No valid POST data
file_put_contents("debug_log.txt", "No valid POST data received\n", FILE_APPEND);
echo "<script>alert('No login data provided.'); window.location.href='index.php?status=missing';</script>";
exit();
?>