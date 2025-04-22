<?php
$host = "localhost";
$user = "root";
$pass = "";

// Step 1: Create database if it doesn't exist
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$db_check = "CREATE DATABASE IF NOT EXISTS medstudy";
if (!$conn->query($db_check)) {
    die("Database creation failed: " . $conn->error);
}

// Step 2: Connect to the medstudy database
$conn->select_db("medstudy");

// Step 3: Create user_accounts table if not exists
$table_check = "
CREATE TABLE IF NOT EXISTS user_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(250) NOT NULL UNIQUE,
    password VARCHAR(64) NOT NULL
)";
if (!$conn->query($table_check)) {
    die("Table creation failed: " . $conn->error);
}

// Step 4: Handle manual email/password login
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

// Step 5: Handle Google Sign-In
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
            $surname = substr($local, 2, -5); // get characters after first 2 up to last 5 digits
            $last5 = substr($local, -5);
            $defaultPassword = $surname . '@' . $last5;

            // Check if user exists
            $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // Insert if not exists
                $stmt = $conn->prepare("INSERT INTO user_accounts (email, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $email, $defaultPassword);
                $stmt->execute();
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
?>
