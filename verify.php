<?php
session_start();
$host = "localhost";
$user = "root"; // Replace with secure user in production
$pass = ""; // Replace with secure password in production
$db = "medstudy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Include PHPMailer files
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Google Sign-In
$id_token = $_POST['credential'] ?? null;
$client_id = "80047855417-dfsmenc4jgtr2me0vm4a5tl76s91bf45.apps.googleusercontent.com";
$id_token = $_POST['credential'] ?? null;

if ($id_token) {
    // Decode the Google ID token
    $payload = json_decode(file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=$id_token"), true);
    if (isset($payload['email']) && $payload['aud'] === $client_id) {
        $email = $payload['email'];
        $domain = explode('@', $email)[1];

        if ($domain === 'usep.edu.ph') {
            $local = explode('@', $email)[0];
            $surname = substr($local, 2, -5);
            $last5 = substr($local, -5);
            $defaultPassword = $surname . '@' . $last5;

            // Check if email exists
            $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            // If new user, insert and send confirmation email
            if ($result->num_rows === 0) {
                $stmt = $conn->prepare("INSERT INTO user_accounts (email, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $email, $defaultPassword);
                if (!$stmt->execute()) {
                    error_log("Insert failed: " . $stmt->error);
                    echo "<script>alert('Failed to insert user.'); window.location.href='index.php';</script>";
                    exit();
                }
                $stmt->close();

                // Set up PHPMailer
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Use your SMTP server
                    $mail->SMTPAuth = true;
                    $mail->Username = 'medstudyhb@gmail.com'; // Gmail email address
                    $mail->Password = 'nshz zntw abwz ymdh'; // Gmail email password or app-specific password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('no-reply@medstudy.local', 'MedStudy');
                    $mail->addAddress($email); // Add recipient

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Welcome to MedStudy - Account Created';
                    $mail->Body    = "Hi,<br><br>Your MedStudy account has been created.<br><br>Email: $email<br>Password: $defaultPassword<br><br>Please log in and change your password.<br><br>Regards,<br>MedStudy Team";

                    // Send email
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Mailer Error: " . $mail->ErrorInfo);
                }
            }

            $_SESSION['email'] = $email;
            header("Location: User/html/home.html");
            exit();
        } else {
            echo "<script>alert('Only @usep.edu.ph emails are allowed.'); window.location.href = 'index.php';</script>";
            exit();
        }
    } else {
        file_put_contents("debug_log.txt", "Invalid Google token or payload: " . print_r($payload, true) . "\n", FILE_APPEND);
        echo "<script>alert('Invalid Google token.'); window.location.href='index.php?status=invalid';</script>";
        exit();
    }
}

// Manual Login
if (isset($_POST['email'], $_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $userAccount = $result->fetch_assoc();
    $stmt->close();

    if ($result && $result->num_rows > 0) {
        $_SESSION['email'] = $email;
        header("Location: User/html/home.html");
        exit();
    } else {
        file_put_contents("debug_log.txt", "Manual login failed: email=$email\n", FILE_APPEND);
        echo "<script>alert('Invalid email or password.'); window.location.href='index.php?status=invalid_credentials';</script>";
        exit();
    }
}
?>