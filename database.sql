CREATE DATABASE IF NOT EXISTS medstudy;
USE medstudy;

-- Table: user_accounts
CREATE TABLE IF NOT EXISTS user_accounts (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(250) NOT NULL UNIQUE,
    password VARCHAR(64) NOT NULL
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
    room_name VARCHAR(250) NOT NULL,
    room_num_stud INT NOT NULL,
    room_num_chair INT NOT NULL,
    room_num_table INT NOT NULL,
    room_status VARCHAR(60) NOT NULL,
    room_qr_code LONGBLOB NOT NULL
);

-- Table: booking
CREATE TABLE IF NOT EXISTS booking (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    room_id INT,
    book_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_details(user_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE
);