CREATE DATABASE IF NOT EXISTS medstudy;
USE medstudy;

-- Table: user_accounts
CREATE TABLE IF NOT EXISTS user_accounts (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(250) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    original_password VARCHAR(255),
    is_temp_password BOOLEAN DEFAULT FALSE,
    temp_password_created_at TIMESTAMP NULL,
    temp_password_expires_at TIMESTAMP NULL
);

-- Table: user_details
CREATE TABLE IF NOT EXISTS user_details (
    user_id INT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(15),
    profile_pic LONGBLOB,
    FOREIGN KEY (user_id) REFERENCES user_accounts(user_id) ON DELETE CASCADE
);

-- Table: rooms
CREATE TABLE IF NOT EXISTS rooms (
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
);

-- Table: booking
CREATE TABLE IF NOT EXISTS booking (
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
);

-- Table: cancelledBooking_Logs
CREATE TABLE IF NOT EXISTS cancelledBooking_Logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    cancellation_reason VARCHAR(255) NOT NULL,
    cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES booking(book_id) ON DELETE CASCADE
);