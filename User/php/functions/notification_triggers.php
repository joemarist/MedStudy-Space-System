<?php
// Database connection parameters
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

// Logging function
function logNotificationError($message, $context = []) {
    $logMessage = $message;
    if (!empty($context)) {
        $logMessage .= "\nContext: " . json_encode($context);
    }
    error_log($logMessage, 3, '/xampp/htdocs/MedStudy-Space-System/notification_error.log');
}

// Create database connection
try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // Log the error
    logNotificationError("Database Connection Error", [
        'error' => $e->getMessage(),
        'host' => $host,
        'user' => $user,
        'db' => $db
    ]);
    
    // Optionally, display a user-friendly error message
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}

// Include notification constants and functions
require_once 'notification_functions.php';

/**
 * Create a notification for successful room booking
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $booking_id Booking ID
 * @param string $room_name Room name
 * @param string $booking_date Booking date
 * @param string $start_time Start time
 * @param string $end_time End time
 * @return bool Success status of notification
 */
function notifyBookingSuccess($conn, $user_id, $booking_id, $room_name, $booking_date, $start_time, $end_time) {
    // Validate input parameters
    if (!$conn || !$user_id || !$booking_id || !$room_name || !$booking_date || !$start_time || !$end_time) {
        logNotificationError("Invalid input parameters for booking success notification", [
            'user_id' => $user_id,
            'booking_id' => $booking_id,
            'room_name' => $room_name,
            'booking_date' => $booking_date,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);
        return false;
    }

    // Convert times to 12-hour format for readability
    $start_time_formatted = date('h:i A', strtotime($start_time));
    $end_time_formatted = date('h:i A', strtotime($end_time));
    
    // Convert booking date to a more readable format
    $booking_date_formatted = date('F d, Y', strtotime($booking_date));

    $message = "Room Booking Successful: $room_name";
    $additional_details = "Date: $booking_date_formatted | Time: $start_time_formatted - $end_time_formatted";
    
    return createNotification(
        $conn, 
        $user_id, 
        NOTIFICATION_BOOKING_SUCCESS, 
        $message, 
        $booking_id, 
        $additional_details
    );
}

/**
 * Create a notification for booking cancellation by user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $booking_id Booking ID
 * @param string $room_name Room name
 * @param string $booking_date Booking date
 * @return bool Success status of notification
 */
function notifyBookingCancelled($conn, $user_id, $booking_id, $room_name, $booking_date) {
    // Convert booking date to a more readable format
    $booking_date_formatted = date('F d, Y', strtotime($booking_date));

    $message = "Booking Cancelled: $room_name";
    $additional_details = "Date: $booking_date_formatted";
    
    return createNotification(
        $conn, 
        $user_id, 
        NOTIFICATION_BOOKING_CANCELLED, 
        $message, 
        $booking_id, 
        $additional_details
    );
}

/**
 * Create a notification for booking cancellation by admin
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $booking_id Booking ID
 * @param string $room_name Room name
 * @param string $booking_date Booking date
 * @param string $cancellation_reason Reason for cancellation (optional)
 * @return bool Success status of notification
 */
function notifyAdminCancellation($conn, $user_id, $booking_id, $room_name, $booking_date, $cancellation_reason = null) {
    // Validate input parameters
    if (!$conn || !$user_id || !$booking_id || !$room_name || !$booking_date) {
        logNotificationError("Invalid input parameters for admin cancellation notification", [
            'user_id' => $user_id,
            'booking_id' => $booking_id,
            'room_name' => $room_name,
            'booking_date' => $booking_date
        ]);
        return false;
    }

    // If no specific reason is provided, fetch from cancelledBooking_Logs
    if ($cancellation_reason === null) {
        $reason_stmt = $conn->prepare("
            SELECT cancellation_reason 
            FROM cancelledBooking_Logs 
            WHERE book_id = ? 
            ORDER BY cancelled_at DESC 
            LIMIT 1
        ");
        
        if (!$reason_stmt) {
            logNotificationError("Failed to prepare reason statement", [
                'error' => $conn->error,
                'booking_id' => $booking_id
            ]);
            return false;
        }
        
        $reason_stmt->bind_param("i", $booking_id);
        $reason_stmt->execute();
        $reason_result = $reason_stmt->get_result();
        
        if ($reason_result->num_rows > 0) {
            $reason_row = $reason_result->fetch_assoc();
            $cancellation_reason = $reason_row['cancellation_reason'];
        } else {
            $cancellation_reason = "No specific reason provided";
        }
        
        $reason_stmt->close();
    }

    // Convert booking date to a more readable format
    $booking_date_formatted = date('F d, Y', strtotime($booking_date));

    $message = "Booking Cancelled by Admin: $room_name";
    $additional_details = "Date: $booking_date_formatted | Reason: $cancellation_reason";
    
    return createNotification(
        $conn, 
        $user_id, 
        NOTIFICATION_ADMIN_CANCELLED, 
        $message, 
        $booking_id, 
        $additional_details
    );
}

/**
 * Create a notification for no-show booking
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $booking_id Booking ID
 * @param string $room_name Room name
 * @param string $booking_date Booking date
 * @return bool Success status of notification
 */
function notifyNoShow($conn, $user_id, $booking_id, $room_name, $booking_date) {
    // Convert booking date to a more readable format
    $booking_date_formatted = date('F d, Y', strtotime($booking_date));

    $message = "Booking Marked as No Show: $room_name";
    $additional_details = "Date: $booking_date_formatted";
    
    return createNotification(
        $conn, 
        $user_id, 
        NOTIFICATION_NO_SHOW, 
        $message, 
        $booking_id, 
        $additional_details
    );
}
?> 