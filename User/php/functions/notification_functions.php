<?php
// Database connection parameters
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

// Create database connection
try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // Log the error
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Optionally, display a user-friendly error message
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}

// Notification Types
define('NOTIFICATION_BOOKING_SUCCESS', 'booking_success');
define('NOTIFICATION_BOOKING_CANCELLED', 'booking_cancelled');
define('NOTIFICATION_ADMIN_CANCELLED', 'admin_cancelled');
define('NOTIFICATION_NO_SHOW', 'no_show');

/**
 * Create a new user notification
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param string $type Notification type
 * @param string $message Notification message
 * @param int|null $booking_id Related booking ID
 * @param string|null $additional_details Additional details
 * @return bool
 */
function createNotification($conn, $user_id, $type, $message, $booking_id = null, $additional_details = null) {
    // Validate input parameters
    if (!$conn) {
        error_log("createNotification: Invalid database connection");
        return false;
    }

    if (!$user_id) {
        error_log("createNotification: Invalid user ID");
        return false;
    }

    // Validate notification type
    $allowed_types = [
        NOTIFICATION_BOOKING_SUCCESS, 
        NOTIFICATION_BOOKING_CANCELLED, 
        NOTIFICATION_ADMIN_CANCELLED, 
        NOTIFICATION_NO_SHOW
    ];

    if (!in_array($type, $allowed_types)) {
        error_log("createNotification: Invalid notification type: $type");
        error_log("Allowed types: " . implode(', ', $allowed_types));
        return false;
    }

    // Ensure message is not null or empty
    if (!$message) {
        $message = 'Notification';
        error_log("createNotification: Empty message provided. Using default.");
    }

    // Truncate message and additional details to prevent database errors
    $message = substr($message, 0, 255);
    $additional_details = substr($additional_details ?? '', 0, 500);

    // Prepare the SQL statement with error handling
    $stmt = $conn->prepare("
        INSERT INTO user_notifications 
        (user_id, booking_id, type, message, additional_details) 
        VALUES (?, ?, ?, ?, ?)
    ");

    // Check if statement preparation failed
    if (!$stmt) {
        error_log("createNotification: Failed to prepare statement");
        error_log("MySQL Error: " . $conn->error);
        return false;
    }

    // Bind parameters
    $stmt->bind_param(
        "iisss", 
        $user_id, 
        $booking_id, 
        $type, 
        $message, 
        $additional_details
    );

    // Use try-catch for more comprehensive error handling
    try {
        // Start a transaction to ensure atomicity
        $conn->begin_transaction();

        // Execute and check result
        $result = $stmt->execute();
        
        // Check execution result
        if (!$result) {
            // Rollback the transaction
            $conn->rollback();

            // Log detailed error information
            error_log("createNotification: Failed to execute notification insertion");
            error_log("MySQL Error: " . $stmt->error);
            error_log("Details - User ID: $user_id, Booking ID: $booking_id, Type: $type");
            error_log("Message: $message");
            error_log("Additional Details: $additional_details");
            
            $stmt->close();
            return false;
        }

        // Commit the transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback in case of any exception
        $conn->rollback();

        // Log the exception
        error_log("createNotification: Exception during notification creation");
        error_log("Exception: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());

        $stmt->close();
        return false;
    }

    // Close statement
    $stmt->close();

    return true;
}

/**
 * Retrieve user notifications
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $limit Number of notifications to retrieve
 * @return array
 */
function getUserNotifications($conn, $user_id, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT notification_id, type, message, additional_details, created_at, is_read
        FROM user_notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");

    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();
    return $notifications;
}

/**
 * Mark notifications as read
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return bool
 */
function markNotificationsAsRead($conn, $user_id) {
    $stmt = $conn->prepare("
        UPDATE user_notifications 
        SET is_read = TRUE 
        WHERE user_id = ? AND is_read = FALSE
    ");

    $stmt->bind_param("i", $user_id);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

/**
 * Get unread notification count
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return int
 */
function getUnreadNotificationCount($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as unread_count 
        FROM user_notifications 
        WHERE user_id = ? AND is_read = FALSE
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['unread_count'];
    $stmt->close();

    return $count;
}
?> 