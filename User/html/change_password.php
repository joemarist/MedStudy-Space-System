<?php
session_start();

// Check if user is logged in and must change password
if (!isset($_SESSION['user_id']) || !isset($_SESSION['must_change_password'])) {
    header("Location: ../../index.php");
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle password change
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate password
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password in database
        $stmt = $conn->prepare("UPDATE user_accounts SET password = ?, is_temp_password = 0 WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Clear temporary password flag
            unset($_SESSION['must_change_password']);
            
            // Log password change
            file_put_contents("../../debug_log.txt", 
                date('[Y-m-d H:i:s] ') . 
                "Password changed for user: {$_SESSION['email']}\n", 
                FILE_APPEND
            );
            
            $success = "Password changed successfully. Please log in.";
            
            // Destroy session and redirect to login
            session_destroy();
            header("Location: ../../index.php?status=password_changed");
            exit();
        } else {
            $error = "Failed to update password. Please try again.";
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | MedStudy</title>
    <link rel="stylesheet" href="../css/change_password.css">
    <link rel="icon" href="../images/logos/medstudyLogo.png">
</head>
<body>
    <div class="container">
        <div class="change-password-box">
            <h2>Change Password</h2>
            <p>You must change your temporary password to continue.</p>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <div class="input-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit">Change Password</button>
            </form>
        </div>
    </div>

    <script>
        // Optional: Add client-side password validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    </script>
</body>
</html> 