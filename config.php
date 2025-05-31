<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Logging function
function logMessage($message) {
    $log_file = dirname(__FILE__) . '/database_init.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Flag to track database initialization
$database_initialized = false;

// Check if database is already initialized
$init_file = dirname(__FILE__) . '/database_initialized.flag';

// Force recreation flag (set to true to force table recreation)
$force_recreation = true;

// Function to mark database as initialized
function markDatabaseInitialized() {
    global $init_file;
    try {
        // Ensure the directory exists
        $dir = dirname($init_file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Create the flag file
        file_put_contents($init_file, time());
        logMessage("Database initialization flag created successfully at $init_file");
        return true;
    } catch (Exception $e) {
        logMessage("Error creating initialization flag: " . $e->getMessage());
        return false;
    }
}

// Function to check if database is initialized
function isDatabaseInitialized() {
    global $init_file, $force_recreation;
    
    // If force recreation is enabled, always return false
    if ($force_recreation) {
        logMessage("Force recreation is enabled. Forcing database reinitialization.");
        return false;
    }
    
    return file_exists($init_file);
}

// Function to safely create complex database objects (triggers, procedures, events)
function createDatabaseObject($conn, $type, $name, $body) {
    try {
        // Drop existing object if it exists
        switch ($type) {
            case 'TRIGGER':
                $conn->query("DROP TRIGGER IF EXISTS $name");
                break;
            case 'PROCEDURE':
                $conn->query("DROP PROCEDURE IF EXISTS $name");
                break;
            case 'EVENT':
                $conn->query("DROP EVENT IF EXISTS $name");
                break;
        }

        // Create the new object
        $result = $conn->multi_query($body);
        
        // Check for errors
        if ($result === false) {
            $error_message = "Failed to create $type $name: " . $conn->error;
            logMessage($error_message);
            logMessage("Problematic SQL: " . $body);
            return false;
        }

        // Clear any remaining results
        while ($conn->next_result()) {
            if ($conn->error) {
                $error_message = "Error in multi_query result for $type $name: " . $conn->error;
                logMessage($error_message);
                logMessage("Problematic SQL: " . $body);
                return false;
            }
        }

        logMessage("Successfully created $type $name");
        return true;
    } catch (Exception $e) {
        $error_message = "Exception creating $type $name: " . $e->getMessage();
        logMessage($error_message);
        logMessage("Problematic SQL: " . $body);
        return false;
    }
}

// SQL Queries for Table Creation
$TABLE_CREATION_QUERIES = [
    // user_accounts table
    "CREATE TABLE IF NOT EXISTS user_accounts (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(250) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        original_password VARCHAR(255),
        is_temp_password BOOLEAN DEFAULT FALSE,
        temp_password_created_at TIMESTAMP NULL,
        temp_password_expires_at TIMESTAMP NULL
    )",

    // user_details table
    "CREATE TABLE IF NOT EXISTS user_details (
        user_id INT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        middle_name VARCHAR(100),
        last_name VARCHAR(100) NOT NULL,
        contact_number VARCHAR(15),
        profile_pic LONGBLOB,
        FOREIGN KEY (user_id) REFERENCES user_accounts(user_id) ON DELETE CASCADE
    )",

    // rooms table
    "CREATE TABLE IF NOT EXISTS rooms (
        room_id INT AUTO_INCREMENT PRIMARY KEY,
        room_name VARCHAR(255) NOT NULL,
        student_capacity INT NOT NULL,
        chairs INT NOT NULL,
        tables INT NOT NULL,
        status VARCHAR(50) NOT NULL,
        room_key VARCHAR(50) UNIQUE NOT NULL,
        qr_code LONGBLOB NOT NULL,
        room_image LONGBLOB NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // booking table
    "CREATE TABLE IF NOT EXISTS booking (
        book_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        room_id INT,
        booking_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        status ENUM('in_process', 'cancelled by student', 'cancelled by admin', 'no_show', 'completed') DEFAULT 'in_process',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user_details(user_id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
        CONSTRAINT valid_duration CHECK (
            TIMESTAMPDIFF(MINUTE, start_time, end_time) BETWEEN 10 AND 120
        ),
        CONSTRAINT valid_time CHECK (
            HOUR(start_time) BETWEEN 8 AND 16 AND
            HOUR(end_time) <= 17 AND
            MINUTE(end_time) <= 59
        ),
        CONSTRAINT no_overlap UNIQUE (room_id, booking_date, start_time, end_time)
    )",

    // cancelledBooking_Logs table
    "CREATE TABLE IF NOT EXISTS cancelledBooking_Logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        book_id INT NOT NULL,
        cancellation_reason VARCHAR(255) NOT NULL,
        cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (book_id) REFERENCES booking(book_id) ON DELETE CASCADE
    )",

    // user_notifications table
    "CREATE TABLE IF NOT EXISTS user_notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        booking_id INT,
        type ENUM(
            'booking_success', 
            'booking_cancelled', 
            'admin_cancelled', 
            'no_show'
        ) NOT NULL,
        message TEXT NOT NULL,
        additional_details TEXT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (user_id) REFERENCES user_accounts(user_id),
        FOREIGN KEY (booking_id) REFERENCES booking(book_id)
    )",

    // Create indexes for user_notifications
    "CREATE INDEX IF NOT EXISTS idx_user_notifications_user_id ON user_notifications(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_user_notifications_created_at ON user_notifications(created_at)",
    "CREATE INDEX IF NOT EXISTS idx_user_notifications_is_read ON user_notifications(is_read)",

    // real_time_activity table
    "CREATE TABLE IF NOT EXISTS real_time_activity (
        activity_id INT AUTO_INCREMENT PRIMARY KEY,
        notification_id INT NOT NULL,
        user_id INT NOT NULL,
        type ENUM(
            'booking_success', 
            'booking_cancelled', 
            'admin_cancelled', 
            'no_show'
        ) NOT NULL,
        message TEXT NOT NULL,
        room_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (notification_id) REFERENCES user_notifications(notification_id),
        FOREIGN KEY (user_id) REFERENCES user_accounts(user_id)
    )"
];

// Trigger Creation Queries
$TRIGGER_CREATION_QUERIES = [
    [
        'name' => 'auto_mark_old_notifications_read',
        'body' => "CREATE TRIGGER auto_mark_old_notifications_read
BEFORE INSERT ON user_notifications
FOR EACH ROW
BEGIN
    -- Use a separate transaction to mark old notifications
    START TRANSACTION;
    
    UPDATE user_notifications 
    SET is_read = TRUE 
    WHERE user_id = NEW.user_id 
      AND is_read = FALSE 
      AND created_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 30 DAY)
    LIMIT 100;  -- Limit to prevent long-running updates
    
    COMMIT;
END;"
    ],
    [
        'name' => 'populate_real_time_activity',
        'body' => "CREATE TRIGGER populate_real_time_activity 
AFTER INSERT ON user_notifications
FOR EACH ROW
BEGIN
    DECLARE room_name VARCHAR(255);
    
    -- Fetch room name based on booking_id
    SELECT COALESCE(r.room_name, 'Unknown Room')
    INTO room_name
    FROM rooms r
    JOIN booking b ON b.room_id = r.room_id
    WHERE b.book_id = NEW.booking_id
    LIMIT 1;
    
    -- Insert into real_time_activity with comprehensive details
    INSERT INTO real_time_activity (
        notification_id, 
        user_id, 
        type, 
        message, 
        room_name
    ) VALUES (
        NEW.notification_id,
        NEW.user_id,
        NEW.type,
        NEW.message,
        room_name
    );
END;"
    ],
    [
        'name' => 'notify_booking_success',
        'body' => "CREATE TRIGGER notify_booking_success 
AFTER INSERT ON booking
FOR EACH ROW
BEGIN
    DECLARE room_name VARCHAR(255);
    DECLARE booking_message VARCHAR(255);
    
    -- Fetch room name with a more robust query
    SELECT 
        COALESCE(r.room_name, 'Unknown Room') 
    INTO room_name 
    FROM rooms r
    WHERE r.room_id = NEW.room_id;
    
    -- Construct message with the fetched room name
    SET booking_message = CONCAT('Room Booking Successful: ', room_name);
    
    -- Insert notification for booking success
    INSERT INTO user_notifications 
    (user_id, booking_id, type, message, additional_details)
    VALUES (
        NEW.user_id, 
        NEW.book_id, 
        'booking_success', 
        booking_message,
        CONCAT(
            'Date: ', COALESCE(DATE_FORMAT(NEW.booking_date, '%M %d, %Y'), 'Unknown Date'), 
            ' | Time: ', 
            COALESCE(DATE_FORMAT(NEW.start_time, '%h:%i %p'), 'Unknown Start'), 
            ' - ', 
            COALESCE(DATE_FORMAT(NEW.end_time, '%h:%i %p'), 'Unknown End'),
            ' | Room: ', 
            room_name
        )
    );
END;"
    ],
    [
        'name' => 'notify_booking_cancelled_by_student',
        'body' => "CREATE TRIGGER notify_booking_cancelled_by_student 
AFTER UPDATE ON booking
FOR EACH ROW
BEGIN
    DECLARE room_name VARCHAR(255);
    DECLARE cancellation_message VARCHAR(255);
    
    -- Check if status changed to 'cancelled by student'
    IF NEW.status = 'cancelled by student' THEN
        -- Fetch room name with a more robust query
        SELECT 
            COALESCE(r.room_name, 'Unknown Room') 
        INTO room_name 
        FROM rooms r
        WHERE r.room_id = NEW.room_id;
        
        -- Construct cancellation message
        SET cancellation_message = CONCAT('Booking Cancelled: ', room_name);
        
        -- Insert notification for booking cancellation
        INSERT INTO user_notifications 
        (user_id, booking_id, type, message, additional_details)
        VALUES (
            NEW.user_id, 
            NEW.book_id, 
            'booking_cancelled', 
            cancellation_message,
            CONCAT(
                'Date: ', COALESCE(DATE_FORMAT(NEW.booking_date, '%M %d, %Y'), 'Unknown Date'),
                ' | Room: ', room_name
            )
        );
    END IF;
END;"
    ],
    [
        'name' => 'notify_booking_cancelled_by_admin',
        'body' => "CREATE TRIGGER notify_booking_cancelled_by_admin 
AFTER UPDATE ON booking
FOR EACH ROW
BEGIN
    DECLARE room_name VARCHAR(255);
    DECLARE cancellation_reason VARCHAR(255);
    DECLARE cancellation_message VARCHAR(255);
    
    -- Check if status changed to 'cancelled by admin'
    IF NEW.status = 'cancelled by admin' THEN
        -- Fetch room name with a more robust query
        SELECT 
            COALESCE(r.room_name, 'Unknown Room') 
        INTO room_name 
        FROM rooms r
        WHERE r.room_id = NEW.room_id;
        
        -- Fetch cancellation reason from cancelledBooking_Logs
        -- Explicitly select the cancellation_reason column
        SELECT cancellation_reason
        INTO cancellation_reason
        FROM cancelledBooking_Logs
        WHERE book_id = NEW.book_id
        ORDER BY cancelled_at DESC
        LIMIT 1;
        
        -- If no reason found, set a default
        IF cancellation_reason IS NULL THEN
            SET cancellation_reason = 'No specific reason provided';
        END IF;
        
        -- Construct cancellation message
        SET cancellation_message = CONCAT('Booking Cancelled by Admin: ', room_name);
        
        -- Insert notification for admin cancellation
        INSERT INTO user_notifications 
        (user_id, booking_id, type, message, additional_details)
        VALUES (
            NEW.user_id, 
            NEW.book_id, 
            'admin_cancelled', 
            cancellation_message,
            CONCAT(
                'Date: ', COALESCE(DATE_FORMAT(NEW.booking_date, '%M %d, %Y'), 'Unknown Date'), 
                ' | Room: ', room_name,
                ' | Reason: ', cancellation_reason
            )
        );
    END IF;
END;"
    ],
    [
        'name' => 'notify_booking_no_show',
        'body' => "CREATE TRIGGER notify_booking_no_show 
AFTER UPDATE ON booking
FOR EACH ROW
BEGIN
    DECLARE room_name VARCHAR(255) DEFAULT 'Unknown Room';
    DECLARE no_show_message VARCHAR(255) DEFAULT 'Booking Marked as No Show';
    
    -- Check if status changed to 'no_show'
    IF NEW.status = 'no_show' THEN
        -- Fetch room name for the notification
        SELECT COALESCE(room_name, 'Unknown Room') INTO room_name 
        FROM rooms 
        WHERE room_id = NEW.room_id;
        
        -- Construct no-show message
        SET no_show_message = CONCAT('Booking Marked as No Show: ', room_name);
        
        -- Insert notification for no-show
        INSERT INTO user_notifications 
        (user_id, booking_id, type, message, additional_details)
        VALUES (
            NEW.user_id, 
            NEW.book_id, 
            'no_show', 
            no_show_message,
            CONCAT('Date: ', COALESCE(DATE_FORMAT(NEW.booking_date, '%M %d, %Y'), 'Unknown Date'))
        );
    END IF;
END;"
    ]
];

// Stored Procedure Queries
$PROCEDURE_CREATION_QUERIES = [
    [
        'name' => 'cleanup_old_notifications',
        'body' => "CREATE PROCEDURE cleanup_old_notifications()
BEGIN
    DELETE FROM user_notifications 
    WHERE created_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 90 DAY);
END;"
    ],
    [
        'name' => 'clear_real_time_activity',
        'body' => "CREATE PROCEDURE clear_real_time_activity()
BEGIN
    TRUNCATE TABLE real_time_activity;
END;"
    ]
];

// Event Queries
$EVENT_CREATION_QUERIES = [
    [
        'name' => 'event_cleanup_notifications',
        'body' => "CREATE EVENT event_cleanup_notifications
ON SCHEDULE EVERY 1 WEEK
DO CALL cleanup_old_notifications()"
    ],
    [
        'name' => 'event_cleanup_real_time_activity',
        'body' => "CREATE EVENT event_cleanup_real_time_activity
ON SCHEDULE EVERY 1 DAY
DO BEGIN
    DELETE FROM real_time_activity 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
END"
    ]
];

// Function to insert initial data
function insertInitialData($conn) {
    try {
        // Removed initial admin account insertion
        logMessage("Skipped initial admin account insertion");
        
        // Removed initial rooms insertion
        logMessage("Skipped initial rooms insertion");
        
        return true;
    } catch (Exception $e) {
        // Log detailed error information
        logMessage("Data Insertion Error: " . $e->getMessage());
        logMessage("Error occurred during: " . $e->getFile() . " at line " . $e->getLine());
        logMessage("Full error trace: " . $e->getTraceAsString());
        
        return false;
    }
}

// Modify the initializeDatabase function to add more robust error handling
function initializeDatabase($conn) {
    global $TABLE_CREATION_QUERIES, $TRIGGER_CREATION_QUERIES, $PROCEDURE_CREATION_QUERIES;
    
    try {
        // Enable events
        $conn->query("SET GLOBAL event_scheduler = ON");
        
        // Create tables
        foreach ($TABLE_CREATION_QUERIES as $query) {
            if (!$conn->query($query)) {
                $error_message = "Failed to create table: " . $conn->error;
                logMessage($error_message);
                logMessage("Problematic query: " . $query);
                throw new Exception($error_message);
            }
        }
        
        // Create triggers
        $trigger_success = true;
        foreach ($TRIGGER_CREATION_QUERIES as $trigger) {
            if (!createDatabaseObject($conn, 'TRIGGER', $trigger['name'], $trigger['body'])) {
                $trigger_success = false;
                logMessage("Failed to create trigger: " . $trigger['name']);
            }
        }
        
        // Create procedures
        $procedure_success = true;
        foreach ($PROCEDURE_CREATION_QUERIES as $procedure) {
            if (!createDatabaseObject($conn, 'PROCEDURE', $procedure['name'], $procedure['body'])) {
                $procedure_success = false;
                logMessage("Failed to create procedure: " . $procedure['name']);
            }
        }
        
        // Insert initial data
        if (!insertInitialData($conn)) {
            throw new Exception("Failed to insert initial data");
        }
        
        // Mark database as initialized
        if (!markDatabaseInitialized()) {
            throw new Exception("Failed to create initialization flag");
        }
        
        // Log any partial failures
        if (!$trigger_success) {
            logMessage("Some triggers failed to create. Check previous log entries.");
        }
        
        if (!$procedure_success) {
            logMessage("Some procedures failed to create. Check previous log entries.");
        }
        
        logMessage("Database initialization completed successfully");
        return true;
    } catch (Exception $e) {
        // Log detailed error information
        logMessage("Database Initialization Error: " . $e->getMessage());
        
        // Additional context logging
        logMessage("Error occurred during: " . $e->getFile() . " at line " . $e->getLine());
        logMessage("Full error trace: " . $e->getTraceAsString());
        
        return false;
    }
}

// Database Connection and Initialization
try {
    // Database connection parameters
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "medstudy";

    // First, connect without specifying a database to create it if needed
    $initial_conn = new mysqli($host, $user, $pass);

    // Check initial connection
    if ($initial_conn->connect_error) {
        throw new Exception("Initial connection failed: " . $initial_conn->connect_error);
    }

    // Create database if it doesn't exist
    $create_db_query = "CREATE DATABASE IF NOT EXISTS `$db`";
    if (!$initial_conn->query($create_db_query)) {
        throw new Exception("Failed to create database: " . $initial_conn->error);
    }

    // Close initial connection
    $initial_conn->close();

    // Now create connection to the specific database
    $conn = new mysqli($host, $user, $pass, $db);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Check if database is already initialized
    if (!isDatabaseInitialized()) {
        logMessage("Database not initialized. Starting initialization process...");
        
        // Attempt to initialize database
        if (!initializeDatabase($conn)) {
            logMessage("Database initialization failed.");
            throw new Exception("Failed to initialize database");
        }
        
        logMessage("Database initialization completed successfully.");
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    // Inline error handling instead of redirecting to error.php
    die("A database error occurred. Please contact the system administrator.");
}
?>