<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

$host = "localhost";
$user = "root";
$pass = "";

// Create DB and connect
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->query("CREATE DATABASE IF NOT EXISTS medstudy");
$conn->select_db("medstudy");
$conn->query("
    CREATE TABLE IF NOT EXISTS user_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(250) NOT NULL UNIQUE,
        password VARCHAR(64) NOT NULL
    )
");

// Email sending function
function sendConfirmationEmail($toEmail, $defaultPassword) {
    $mail = new PHPMailer(true);
    try {
        // SMTP SETTINGS (Gmail example)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'medstudyhb@gmail.com'; // 🔐 Your email
        $mail->Password   = 'nshz zntw abwz ymdh';   // 🔐 Use app password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Content
        $mail->setFrom('medstudyhb@gmail.com', 'MedStudy Space System');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Your MedStudy Account Password';
        $mail->Body    = "Welcome to MedStudy!<br><br>Your account has been successfully created.<br><strong>Password:</strong> $defaultPassword<br><br>Please log in and change your password if needed.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Google Sign-In
$id_token = $_POST['credential'] ?? null;
$client_id = "80047855417-dfsmenc4jgtr2me0vm4a5tl76s91bf45.apps.googleusercontent.com";

if ($id_token) {
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = file_get_contents($url);
    $payload = json_decode($response, true);

    if (isset($payload['email']) && $payload['aud'] === $client_id) {
        $email = $payload['email'];
        $domain = explode('@', $email)[1];

        if ($domain === 'usep.edu.ph') {
            $local = explode('@', $email)[0];
            $surname = substr($local, 2, -5);
            $last5 = substr($local, -5);
            $defaultPassword = $surname . '@' . $last5;

            // Check if user exists
            $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // Insert & Email
                $stmt = $conn->prepare("INSERT INTO user_accounts (email, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $email, $defaultPassword);
                $stmt->execute();

                if (sendConfirmationEmail($email, $defaultPassword)) {
                    echo "<script>alert('Account created! Password has been sent to your email.');</script>";
                } else {
                    echo "<script>alert('Account created! But failed to send email.');</script>";
                }
            }

            session_start();
            $_SESSION['email'] = $email;
            header("Location: User/html/home.html");
            exit();
        } else {
            echo "<script>alert('Only @usep.edu.ph emails are allowed.'); window.location.href = 'index.html';</script>";
        }
    } else {
        echo "<script>alert('Invalid token or client ID.'); window.location.href = 'index.html';</script>";
    }
}

// Manual login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        session_start();
        $_SESSION['email'] = $email;
        header("Location: User/html/home.html");
        exit();
    } else {
        echo "<script>alert('Invalid email or password.'); window.location.href = 'index.html';</script>";
        exit();
    }
}
?>
