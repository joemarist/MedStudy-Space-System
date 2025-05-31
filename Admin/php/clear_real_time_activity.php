<?php
// Database connection parameters
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

// Establish database connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode([
        'success' => false, 
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Prepare and execute clear activity query
$clear_query = "DELETE FROM real_time_activity";
$result = $conn->query($clear_query);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Real-time activity cleared successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to clear real-time activity: ' . $conn->error
    ]);
}

$conn->close();
?> 