<?php
// Enable full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/xampp/htdocs/MedStudy-Space-System/booking_error.log');

// Ensure clean JSON output
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Clear any existing output buffers
ob_clean();
ob_start();

session_start();
date_default_timezone_set('Asia/Manila');

// Debug logging function
function debugLog($message, $data = null) {
    $log_message = "[BOOKING] " . $message;
    if ($data !== null) {
        $log_message .= " | " . (is_array($data) ? json_encode($data) : $data);
    }
    
    error_log($log_message);
}

debugLog("=== Process Booking Start ===");

// Include notification triggers
require_once 'functions/notification_triggers.php';

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    debugLog("Database Connection Error", $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    debugLog("User not logged in");
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get booking data from POST request
$raw_input = file_get_contents('php://input');
debugLog("Raw Input", $raw_input);

$data = json_decode($raw_input, true);
debugLog("Decoded Data", $data);

// Validate input with detailed logging
$required_fields = ['room_id', 'booking_date', 'start_time', 'end_time'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    debugLog("Missing Required Fields", $missing_fields);
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$room_id = $conn->real_escape_string($data['room_id']);

// Convert the date to Manila timezone
$booking_date_obj = new DateTime($data['booking_date'], new DateTimeZone('UTC'));
$booking_date_obj->setTimezone(new DateTimeZone('Asia/Manila'));
$booking_date = $booking_date_obj->format('Y-m-d');

debugLog("Original booking date: " . $data['booking_date']);
debugLog("Converted booking date: " . $booking_date);

// Check if user already has a booking on this date
$check_existing_query = "SELECT book_id, status FROM booking 
                        WHERE user_id = ? AND booking_date = ?";
$stmt = $conn->prepare($check_existing_query);
$stmt->bind_param("is", $user_id, $booking_date);
$stmt->execute();
$result = $stmt->get_result();
$existing_booking = $result->fetch_assoc();

// Log detailed booking information for debugging
debugLog("Existing Booking Check:");
debugLog("User ID: " . $user_id);
debugLog("Booking Date: " . $booking_date);
debugLog("Existing Booking: " . print_r($existing_booking, true));

if ($existing_booking) {
    debugLog("User already has a booking on this date");
    echo json_encode([
        'success' => false, 
        'message' => 'You already have a booking on this date. To ensure fair access for all students, only one booking per day is allowed.',
        'error_type' => 'duplicate_booking'
    ]);
    exit;
}

// Convert times to 24-hour format in Manila timezone
$start_time_obj = DateTime::createFromFormat('h:i A', $data['start_time'], new DateTimeZone('Asia/Manila'));
$end_time_obj = DateTime::createFromFormat('h:i A', $data['end_time'], new DateTimeZone('Asia/Manila'));

if (!$start_time_obj || !$end_time_obj) {
    debugLog("Time format conversion failed");
    debugLog("Start time: " . $data['start_time']);
    debugLog("End time: " . $data['end_time']);
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit;
}

$start_time = $start_time_obj->format('H:i:s');
$end_time = $end_time_obj->format('H:i:s');

debugLog("Original start time: " . $data['start_time']);
debugLog("Original end time: " . $data['end_time']);
debugLog("Converted start time: " . $start_time);
debugLog("Converted end time: " . $end_time);

// Validate booking time (8am to 5pm)
function isValidBookingTime($start_time, $end_time) {
    // Convert times to DateTime objects
    $start_datetime = DateTime::createFromFormat('H:i:s', $start_time);
    $end_datetime = DateTime::createFromFormat('H:i:s', $end_time);
    
    // Extract hours
    $start_hour = intval($start_datetime->format('H'));
    $end_hour = intval($end_datetime->format('H'));
    
    // Check if booking is between 8am and 5pm
    return $start_hour >= 8 && $end_hour <= 17;
}

// Validate booking time
if (!isValidBookingTime($start_time, $end_time)) {
    debugLog("Invalid booking time: $start_time to $end_time");
    echo json_encode([
        'success' => false, 
        'message' => 'Bookings are only allowed between 8:00 AM and 5:00 PM',
        'error_type' => 'invalid_time'
    ]);
    exit;
}

// Check booking duration (minimum 10 minutes, maximum 2 hours)
$start_datetime = DateTime::createFromFormat('H:i:s', $start_time);
$end_datetime = DateTime::createFromFormat('H:i:s', $end_time);
$duration = $start_datetime->diff($end_datetime);
$hours = $duration->h + ($duration->i / 60);

if ($hours < (10/60)) {
    debugLog("Booking duration too short: $hours hours");
    echo json_encode([
        'success' => false, 
        'message' => 'Minimum booking duration is 10 minutes',
        'error_type' => 'short_duration'
    ]);
    exit;
}

if ($hours > 2) {
    debugLog("Booking duration too long: $hours hours");
    echo json_encode([
        'success' => false, 
        'message' => 'Maximum booking duration is 2 hours',
        'error_type' => 'long_duration'
    ]);
    exit;
}

// Check for existing bookings in the same time slot
$check_query = "SELECT book_id, status FROM booking 
                WHERE room_id = ? 
                AND booking_date = ? 
                AND (
                    (start_time <= ? AND end_time > ?) OR
                    (start_time < ? AND end_time >= ?) OR
                    (start_time >= ? AND end_time <= ?)
                )";

$stmt = $conn->prepare($check_query);
$stmt->bind_param("isssssss", $room_id, $booking_date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time);
$stmt->execute();
$result = $stmt->get_result();
$conflicting_booking = $result->fetch_assoc();

// Log detailed time slot check for debugging
debugLog("Time Slot Check:");
debugLog("Room ID: " . $room_id);
debugLog("Booking Date: " . $booking_date);
debugLog("Start Time: " . $start_time);
debugLog("End Time: " . $end_time);
debugLog("Conflicting Booking: " . print_r($conflicting_booking, true));

if ($conflicting_booking && $conflicting_booking['status'] !== 'cancelled') {
    debugLog("Time slot already booked");
    echo json_encode(['success' => false, 'message' => 'This time slot is already booked']);
    exit;
}

// Insert new booking
$insert_query = "INSERT INTO booking (user_id, room_id, booking_date, start_time, end_time) 
                 VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insert_query);
$stmt->bind_param("iisss", $user_id, $room_id, $booking_date, $start_time, $end_time);

if ($stmt->execute()) {
    // Get the newly inserted booking ID
    $booking_id = $conn->insert_id;

    // Ensure clean JSON output
    echo json_encode(['success' => true, 'message' => 'Booking successful']);
    exit;
} else {
    // Ensure clean JSON output for failure
    echo json_encode(['success' => false, 'message' => 'Failed to create booking: ' . $conn->error]);
    exit;
}

$stmt->close();
$conn->close();
?> 