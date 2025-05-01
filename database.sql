CREATE DATABASE IF NOT EXISTS medstudy;

USE medstudy;

CREATE TABLE IF NOT EXISTS user_accounts (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(250) NOT NULL UNIQUE,
    password VARCHAR(64) NOT NULL
);

CREATE TABLE IF NOT EXISTS user_details (
    user_id INT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(15),
    email VARCHAR(250) NOT NULL,
    profile_pic BLOB NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user_accounts(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(250) NOT NULL,
    room_num_stud INT NOT NULL,
    room_num_chair INT NOT NULL,
    room_num_table INT NOT NULL,
    room_status VARCHAR(60) NOT NULL,
    room_qr_code BLOB NOT NULL
);

CREATE TABLE IF NOT EXISTS booking (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    room_id INT,
    book_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_details(user_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE
);
