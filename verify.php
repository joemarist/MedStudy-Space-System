<?php
session_start();

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Update require paths to match actual PHPMailer directory structure
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to generate default password based on email
function generateDefaultPassword($email) {
    // Extract username part before @ 
    $parts = explode('@', $email);
    $username = $parts[0];
    
    // Remove first two words (assuming username follows a pattern)
    $usernameParts = explode('.', $username);
    $lastName = count($usernameParts) > 1 ? $usernameParts[1] : $username;
    
    // Extract last 5 digits from the email
    $digits = preg_replace('/[^0-9]/', '', $email);
    $lastFiveDigits = substr($digits, -5);
    
    // Construct password: lastName@lastFiveDigits
    return $lastName . '@' . $lastFiveDigits;
}

// Function to send welcome email using PHPMailer
function sendWelcomeEmail($email, $password) {
    $mail = null;
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Gmail SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'medstudyhb@gmail.com';  // Your Gmail email
        $mail->Password   = 'nshz zntw abwz ymdh';  // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('medstudyhb@gmail.com', 'MedStudy');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to MedStudy - Your Account Details';
        $mail->Body    = "
            <html>
            <body>
                <h2>Welcome to MedStudy!</h2>
                <p>Your account has been created successfully.</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Password:</strong> {$password}</p>
                <p>Please log in and change your password immediately.</p>
                <p>Best regards,<br>MedStudy Team</p>
            </body>
            </html>
        ";
        $mail->AltBody = "Welcome to MedStudy!\n\nYour account has been created successfully.\n\nEmail: {$email}\nPassword: {$password}\n\nPlease log in and change your password immediately.\n\nBest regards,\nMedStudy Team";

        $mail->send();
        file_put_contents("email_log.txt", 
            date('[Y-m-d H:i:s] ') . 
            "Email sent successfully to $email\n", 
            FILE_APPEND
        );
        return true;
    } catch (Exception $e) {
        // Log detailed error
        $errorInfo = $mail ? $mail->ErrorInfo : $e->getMessage();
        file_put_contents("email_error_log.txt", 
            date('[Y-m-d H:i:s] ') . 
            "Email sending failed to $email: " . $errorInfo . "\n", 
            FILE_APPEND
        );
        return false;
    }
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

        // Generate default password based on email
        $defaultPassword = generateDefaultPassword($email);
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("SELECT user_id FROM user_accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingUser = $result->fetch_assoc();
        $stmt->close();

        if (!$existingUser) {
            $stmt = $conn->prepare("INSERT INTO user_accounts (email, password, original_password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $hashedPassword, $defaultPassword);
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                file_put_contents("debug_log.txt", "New user created: user_id=$user_id, email=$email\n", FILE_APPEND);

                // Send welcome email with default password
                sendWelcomeEmail($email, $defaultPassword);
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

        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, middle_name, profile_pic FROM user_details WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $detailsExist = $result->fetch_assoc();
        $stmt->close();

        if (!$detailsExist) {
            $stmt = $conn->prepare("INSERT INTO user_details (user_id, first_name, last_name, middle_name, profile_pic) VALUES (?, ?, ?, ?, ?)");
            $null = null;
            $stmt->bind_param("isssb", $user_id, $firstName, $lastName, $null, $null);
            if ($profilePicBlob) {
                $stmt->send_long_data(4, $profilePicBlob);
            }
            if ($stmt->execute()) {
                file_put_contents("debug_log.txt", "User details inserted: user_id=$user_id, first_name=$firstName, last_name=$lastName, middle_name=null, profile_pic=" . ($profilePicBlob ? "set" : "null") . "\n", FILE_APPEND);
            } else {
                file_put_contents("debug_log.txt", "Insert into user_details failed: " . $stmt->error . "\n", FILE_APPEND);
                echo "<script>alert('Failed to save user details.'); window.location.href='index.php?status=error';</script>";
                exit();
            }
            $stmt->close();
        } else {
            $firstName = $detailsExist['first_name'];
            $lastName = $detailsExist['last_name'];
            $middleName = $detailsExist['middle_name'];
            $profilePicBlob = $detailsExist['profile_pic'];
            file_put_contents("debug_log.txt", "User details exist: user_id=$user_id, first_name=$firstName, last_name=$lastName, middle_name=" . ($middleName ?? "null") . ", profile_pic=" . ($profilePicBlob ? "set" : "null") . "\n", FILE_APPEND);
        }

        session_unset();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['middle_name'] = $middleName ?? null;
        $_SESSION['profile_pic'] = $profilePicBlob ? 'data:image/jpeg;base64,' . base64_encode($profilePicBlob) : null;
        $_SESSION['last_activity'] = time();
        file_put_contents("debug_log.txt", "Session set: user_id=$user_id, email=$email, first_name=$firstName, last_name=$lastName, middle_name=" . ($_SESSION['middle_name'] ?? "null") . ", profile_pic=" . ($_SESSION['profile_pic'] ? "set" : "null") . "\n", FILE_APPEND);

        header("Location: index.php?status=success");
        exit();
    } else {
        file_put_contents("debug_log.txt", "Invalid Google token or payload: " . print_r($payload, true) . "\n", FILE_APPEND);
        echo "<script>alert('Invalid Google token.'); window.location.href='index.php?status=invalid';</script>";
        exit();
    }
}

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!str_ends_with($email, '@usep.edu.ph')) {
        file_put_contents("debug_log.txt", "Non-usep.edu.ph email detected for manual login: $email\n", FILE_APPEND);
        echo "<script>alert('Only @usep.edu.ph accounts are allowed.'); window.location.href='index.php?status=denied';</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT user_id, password FROM user_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $userAccount = $result->fetch_assoc();
    $stmt->close();

    // Verify if user exists and password is correct
    if ($userAccount && password_verify($password, $userAccount['password'])) {
        $user_id = $userAccount['user_id'];

        // Check if this is a temporary password
        $stmt = $conn->prepare("SELECT is_temp_password FROM user_accounts WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $passwordStatus = $result->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("SELECT first_name, last_name, middle_name, profile_pic FROM user_details WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetails = $result->fetch_assoc();
        $stmt->close();

        session_unset();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $userDetails['first_name'] ?? 'Firstname';
        $_SESSION['last_name'] = $userDetails['last_name'] ?? 'Lastname';
        $_SESSION['middle_name'] = $userDetails['middle_name'] ?? null;
        $_SESSION['profile_pic'] = $userDetails['profile_pic'] ? 'data:image/jpeg;base64,' . base64_encode($userDetails['profile_pic']) : null;
        $_SESSION['last_activity'] = time();
        
        // If temporary password is used, force password change
        if ($passwordStatus['is_temp_password'] == 1) {
            $_SESSION['must_change_password'] = true;
            
            // Log temporary password login
            file_put_contents("debug_log.txt", "Temporary password login: email=$email\n", FILE_APPEND);
            
            // Redirect to password change page
            header("Location: User/html/change_password.php?temp_login=1");
            exit();
        }
        
        // Log successful login
        file_put_contents("debug_log.txt", "Manual login session set: user_id=$user_id, email=$email, first_name={$_SESSION['first_name']}, last_name={$_SESSION['last_name']}, middle_name=" . ($_SESSION['middle_name'] ?? "null") . ", profile_pic=" . ($_SESSION['profile_pic'] ? "set" : "null") . "\n", FILE_APPEND);

        header("Location: index.php?status=success");
        exit();
    } else {
        // Log failed login attempt
        file_put_contents("debug_log.txt", "Manual login failed: email=$email (Incorrect credentials)\n", FILE_APPEND);
        
        // Specific error message for login failure
        echo "<script>
        alert('Login failed. Please check your email and password.');
        window.location.href='index.php?status=login_failed';
        </script>";
        exit();
    }
}

file_put_contents("debug_log.txt", "No valid POST data received\n", FILE_APPEND);
echo "<script>alert('No login data provided.'); window.location.href='index.php?status=missing';</script>";
exit();
?>