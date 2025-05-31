<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure no previous output
if (ob_get_level()) {
    @ob_end_clean();
}

// Set JSON headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Include necessary PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Update require paths to match actual PHPMailer directory structure
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Database connection details
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

// Prepare default response
$response = [
    'success' => false,
    'message' => 'An unexpected error occurred'
];

// Function to generate a secure temporary password
function generateTemporaryPassword($length = 12) {
    // Character sets
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    // Combine character sets
    $allChars = $uppercase . $lowercase . $numbers . $special;
    
    // Ensure at least one character from each set
    $password = 
        $uppercase[random_int(0, strlen($uppercase) - 1)] .
        $lowercase[random_int(0, strlen($lowercase) - 1)] .
        $numbers[random_int(0, strlen($numbers) - 1)] .
        $special[random_int(0, strlen($special) - 1)];
    
    // Fill the rest of the password
    for ($i = 4; $i < $length; $i++) {
        $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }
    
    // Shuffle the password to randomize character positions
    $passwordArray = str_split($password);
    shuffle($passwordArray);
    return implode('', $passwordArray);
}

// Function to send temporary password email
function sendTemporaryPasswordEmail($email, $tempPassword) {
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
        $mail->setFrom('medstudyhb@gmail.com', 'MedStudy Password Recovery');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'MedStudy - Temporary Password';
        $mail->Body    = "
            <html>
            <body>
                <h2>MedStudy Temporary Password</h2>
                <p>A temporary password has been generated for your account:</p>
                <p><strong>Temporary Password:</strong> {$tempPassword}</p>
                <p>Please log in and change your password immediately.</p>
                <p>This temporary password will expire soon.</p>
                <p>Best regards,<br>MedStudy Team</p>
            </body>
            </html>
        ";
        $mail->AltBody = "MedStudy Temporary Password\n\nA temporary password has been generated for your account:\n\nTemporary Password: {$tempPassword}\n\nPlease log in and change your password immediately.\nThis temporary password will expire soon.\n\nBest regards,\nMedStudy Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log detailed error
        $errorInfo = $mail ? $mail->ErrorInfo : $e->getMessage();
        return false;
    }
}

try {
    // Create database connection
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate email
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : null;

    if (!$email) {
        throw new Exception('Invalid email address');
    }

    // Check if email exists in user_accounts
    $stmt = $conn->prepare("SELECT user_id FROM user_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Email not found
        $response = [
            'success' => false,
            'message' => 'No account found with this email address'
        ];
        $stmt->close();
    } else {
        // Retrieve user details
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $stmt->close();

        // Generate temporary password
        $tempPassword = generateTemporaryPassword();
        
        // Hash the temporary password
        $hashedTempPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Update user's password with temporary password
        $stmt = $conn->prepare("UPDATE user_accounts SET password = ?, is_temp_password = 1 WHERE user_id = ?");
        $stmt->bind_param("si", $hashedTempPassword, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update temporary password');
        }
        $stmt->close();
        
        // Send temporary password email
        $emailSent = sendTemporaryPasswordEmail($email, $tempPassword);
        
        if ($emailSent) {
            $response = [
                'success' => true,
                'message' => 'Temporary password sent to your email. Please check your inbox.'
            ];
        } else {
            throw new Exception('Failed to send temporary password email');
        }
    }
} catch (Exception $e) {
    // Update response with error message
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
} finally {
    // Close connection
    if (isset($conn) && $conn) {
        $conn->close();
    }

    // Output JSON response
    echo json_encode($response);
    exit;
}
?> 