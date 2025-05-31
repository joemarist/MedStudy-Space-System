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
    error_log("Database Connection Error: " . $conn->connect_error);
    die("A database connection error occurred. Please contact the system administrator.");
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
        return true;
    } catch (Exception $e) {
        // Log detailed error
        $errorInfo = $mail ? $mail->ErrorInfo : $e->getMessage();
        return false;
    }
}

$client_id = "80047855417-dfsmenc4jgtr2me0vm4a5tl76s91bf45.apps.googleusercontent.com";
$id_token = $_POST['credential'] ?? null;

if ($id_token) {
    $payload = json_decode(file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=$id_token"), true);

    if ($payload && isset($payload['email']) && $payload['aud'] === $client_id && $payload['iss'] === 'https://accounts.google.com') {
        $email = $payload['email'];
        $firstName = $payload['given_name'] ?? 'Firstname';
        $lastName = $payload['family_name'] ?? 'Lastname';
        $profilePicUrl = $payload['picture'] ?? null;

        $profilePicBlob = null;
        if ($profilePicUrl) {
            $profilePicBlob = @file_get_contents($profilePicUrl);
        }

        if (!str_ends_with($email, '@usep.edu.ph')) {
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

                // Send welcome email with default password
                sendWelcomeEmail($email, $defaultPassword);
            } else {
                echo "<script>alert('Failed to create user account.'); window.location.href='index.php?status=error';</script>";
                exit();
            }
            $stmt->close();
        } else {
            $user_id = $existingUser['user_id'];
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
            } else {
                echo "<script>alert('Failed to save user details.'); window.location.href='index.php?status=error';</script>";
                exit();
            }
            $stmt->close();
        } else {
            $firstName = $detailsExist['first_name'];
            $lastName = $detailsExist['last_name'];
            $middleName = $detailsExist['middle_name'];
            $profilePicBlob = $detailsExist['profile_pic'];
        }

        session_unset();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['middle_name'] = $middleName ?? null;
        $_SESSION['profile_pic'] = $profilePicBlob ? 'data:image/jpeg;base64,' . base64_encode($profilePicBlob) : null;
        $_SESSION['last_activity'] = time();

        header("Location: index.php?status=success");
        exit();
    } else {
        echo "<script>alert('Invalid Google token.'); window.location.href='index.php?status=invalid';</script>";
        exit();
    }
}

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!str_ends_with($email, '@usep.edu.ph')) {
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
            
            // Redirect to password change page
            header("Location: User/html/change_password.php?temp_login=1");
            exit();
        }

        header("Location: index.php?status=success");
        exit();
    } else {
        echo "<script>
        alert('Login failed. Please check your email and password.');
        window.location.href='index.php?status=login_failed';
        </script>";
        exit();
    }
}

echo "<script>alert('No login data provided.'); window.location.href='index.php?status=missing';</script>";
exit();
?>