<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

// Debug logging
error_log("=== Process Booking Debug ===");

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    die(json_encode(['success' => false, 'message' => 'User not logged in']));
}

// Get booking data from POST request
$data = json_decode(file_get_contents('php://input'), true);
error_log("Received booking data: " . print_r($data, true));

if (!isset($data['room_id'], $data['booking_date'], $data['start_time'], $data['end_time'])) {
    error_log("Missing required fields");
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

$user_id = $_SESSION['user_id'];
$room_id = $conn->real_escape_string($data['room_id']);

// Convert the date to Manila timezone
$booking_date_obj = new DateTime($data['booking_date'], new DateTimeZone('UTC'));
$booking_date_obj->setTimezone(new DateTimeZone('Asia/Manila'));
$booking_date = $booking_date_obj->format('Y-m-d');

error_log("Original booking date: " . $data['booking_date']);
error_log("Converted booking date: " . $booking_date);

// Check if user already has a booking on this date
$check_existing_query = "SELECT book_id, status FROM booking 
                        WHERE user_id = ? AND booking_date = ?";
$stmt = $conn->prepare($check_existing_query);
$stmt->bind_param("is", $user_id, $booking_date);
$stmt->execute();
$result = $stmt->get_result();
$existing_booking = $result->fetch_assoc();

// Log detailed booking information for debugging
error_log("Existing Booking Check:");
error_log("User ID: " . $user_id);
error_log("Booking Date: " . $booking_date);
error_log("Existing Booking: " . print_r($existing_booking, true));

if ($existing_booking) {
    error_log("User already has a booking on this date");
    die(json_encode([
        'success' => false, 
        'message' => 'You already have a booking on this date. To ensure fair access for all students, only one booking per day is allowed.',
        'error_type' => 'duplicate_booking'
    ]));
}

// Convert times to 24-hour format in Manila timezone
$start_time_obj = DateTime::createFromFormat('h:i A', $data['start_time'], new DateTimeZone('Asia/Manila'));
$end_time_obj = DateTime::createFromFormat('h:i A', $data['end_time'], new DateTimeZone('Asia/Manila'));

if (!$start_time_obj || !$end_time_obj) {
    error_log("Time format conversion failed");
    error_log("Start time: " . $data['start_time']);
    error_log("End time: " . $data['end_time']);
    die(json_encode(['success' => false, 'message' => 'Invalid time format']));
}

$start_time = $start_time_obj->format('H:i:s');
$end_time = $end_time_obj->format('H:i:s');

error_log("Original start time: " . $data['start_time']);
error_log("Original end time: " . $data['end_time']);
error_log("Converted start time: " . $start_time);
error_log("Converted end time: " . $end_time);

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
    error_log("Invalid booking time: $start_time to $end_time");
    die(json_encode([
        'success' => false, 
        'message' => 'Bookings are only allowed between 8:00 AM and 5:00 PM',
        'error_type' => 'invalid_time'
    ]));
}

// Check booking duration (minimum 10 minutes, maximum 2 hours)
$start_datetime = DateTime::createFromFormat('H:i:s', $start_time);
$end_datetime = DateTime::createFromFormat('H:i:s', $end_time);
$duration = $start_datetime->diff($end_datetime);
$hours = $duration->h + ($duration->i / 60);

if ($hours < (10/60)) {
    error_log("Booking duration too short: $hours hours");
    die(json_encode([
        'success' => false, 
        'message' => 'Minimum booking duration is 10 minutes',
        'error_type' => 'short_duration'
    ]));
}

if ($hours > 2) {
    error_log("Booking duration too long: $hours hours");
    die(json_encode([
        'success' => false, 
        'message' => 'Maximum booking duration is 2 hours',
        'error_type' => 'long_duration'
    ]));
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
error_log("Time Slot Check:");
error_log("Room ID: " . $room_id);
error_log("Booking Date: " . $booking_date);
error_log("Start Time: " . $start_time);
error_log("End Time: " . $end_time);
error_log("Conflicting Booking: " . print_r($conflicting_booking, true));

if ($conflicting_booking && $conflicting_booking['status'] !== 'cancelled') {
    error_log("Time slot already booked");
    die(json_encode(['success' => false, 'message' => 'This time slot is already booked']));
}

// Insert new booking
$insert_query = "INSERT INTO booking (user_id, room_id, booking_date, start_time, end_time) 
                 VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insert_query);
$stmt->bind_param("iisss", $user_id, $room_id, $booking_date, $start_time, $end_time);

if ($stmt->execute()) {
    error_log("Booking successful");
    error_log("Inserted booking - Date: $booking_date, Start: $start_time, End: $end_time");
    echo json_encode(['success' => true, 'message' => 'Booking successful']);
} else {
    error_log("Booking failed: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Failed to create booking: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 