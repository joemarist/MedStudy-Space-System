<?php
header('Content-Type: application/json');

// Database connection parameters
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

// Establish database connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Query to fetch recent activities from user_notifications
$activities_query = "
SELECT 
    un.type AS activity_type,
    ud.first_name, 
    ud.last_name, 
    COALESCE(r.room_name, 'Notification') AS room_name,
    un.created_at AS activity_time
FROM user_notifications un
JOIN user_accounts ua ON un.user_id = ua.user_id
JOIN user_details ud ON ua.user_id = ud.user_id
LEFT JOIN booking b ON un.booking_id = b.book_id
LEFT JOIN rooms r ON b.room_id = r.room_id
ORDER BY un.created_at DESC
LIMIT 10
";

$activities_result = $conn->query($activities_query);
$activities = [];

if ($activities_result) {
    while ($row = $activities_result->fetch_assoc()) {
        $activities[] = $row;
    }
}

// Optional: Clear the admin_notifications table after fetching
$clear_query = "DELETE FROM admin_notifications";
$conn->query($clear_query);

$conn->close();

// Output activities as JSON
echo json_encode($activities); 