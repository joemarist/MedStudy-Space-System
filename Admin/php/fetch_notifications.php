<?php
// Database connection parameters
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

// Establish database connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// First, clear the real_time_activity table
$clear_query = "CALL clear_real_time_activity()";
$conn->query($clear_query);

// Fetch recent real-time activities with user details
$notifications_query = "
    SELECT 
        rta.activity_id,
        rta.type,
        rta.message,
        rta.created_at,
        ud.first_name,
        ud.last_name,
        rta.room_name
    FROM 
        real_time_activity rta
    JOIN 
        user_details ud ON rta.user_id = ud.user_id
    ORDER BY 
        rta.created_at DESC
    LIMIT 10
";

$result = $conn->query($notifications_query);

if ($result && $result->num_rows > 0) {
    while ($notification = $result->fetch_assoc()) {
        // Determine icon based on notification type
        $icon = match($notification['type']) {
            'booking_success' => '📅',
            'booking_cancelled' => '❌',
            'admin_cancelled' => '🚫',
            'no_show' => '⏰',
            default => '📢'
        };

        // Format notification message
        $full_name = htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']);
        $room_name = htmlspecialchars($notification['room_name'] ?? 'Unknown Room');
        $message = match($notification['type']) {
            'booking_success' => "$full_name booked $room_name",
            'booking_cancelled' => "$full_name cancelled booking",
            'admin_cancelled' => "Booking for $full_name cancelled by admin",
            'no_show' => "$full_name missed a booking",
            default => $notification['message']
        };

        echo "<div class='activity-item' data-activity-id='{$notification['activity_id']}' data-notification-type='{$notification['type']}'>
                <span class='icon'>{$icon}</span>
                <span class='message'>{$message}</span>
                <span class='timestamp'>" . 
                date('H:i', strtotime($notification['created_at'])) . 
                "</span>
              </div>";
    }
} else {
    echo "<div class='activity-item'>No recent activities</div>";
}

$conn->close();
?> 