<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['email'])) {
    header("Location: /MedStudy-Space-System/index.php");
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT ud.first_name, ud.last_name, ud.contact_number, ud.profile_pic 
                       FROM user_details ud 
                       JOIN user_accounts ua ON ud.user_id = ua.user_id 
                       WHERE ua.email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Use session data as fallback
$first_name = $user['first_name'] ?? $_SESSION['first_name'] ?? 'Firstname';
$last_name = $user['last_name'] ?? $_SESSION['last_name'] ?? 'Lastname';
$contact_number = $user['contact_number'] ?? '';
$profile_pic = $user['profile_pic'] ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : '/MedStudy-Space-System/User/images/icons/black.jpg';
$full_name = $first_name . ' ' . $last_name;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedStudy | Home</title>
    <link rel="stylesheet" href="/MedStudy-Space-System/User/css/home.css">
    <link rel="icon" href="/MedStudy-Space-System/User/images/logos/medstudyLogo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.10/index.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.10/index.global.min.js"></script>
    <script src="/MedStudy-Space-System/User/js/calendar.js"></script>
    <script src="/MedStudy-Space-System/User/js/linkActive.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
    <div class="navBar">
        <div class="leftNav">
            <div class="logo" onclick="location.href='/MedStudy-Space-System/User/html/home.php'">
                <h2>
                    <img src="/MedStudy-Space-System/User/images/logos/medstudyLogoResized.png" alt="">
                    <span>
                        <span style="color: #52BBBF;">Med</span>Study
                    </span>
                </h2>
            </div>
            <div class="navLinks">
                <a href="/MedStudy-Space-System/User/html/home.php" class="link active">Home</a>
                <a href="/MedStudy-Space-System/User/html/schedule.php" class="link">Schedule</a>
            </div>
        </div>
        <div class="rightNav">
            <button onclick="location.href='/MedStudy-Space-System/User/php/logout.php'">Log out</button>
        </div>
    </div>

    <div align="right" class="logout-mobile">
        <img src="/MedStudy-Space-System/User/images/icons/logout3.png" alt="" onclick="location.href='/MedStudy-Space-System/User/php/logout.php'">
        <span>Log out</span>
    </div>

    <div class="headerIntro">
        <div class="introInfo">
            <h1>
                Reserve Your <br>
                Study Space Instantly.
                <img src="/MedStudy-Space-System/User/images/icons/line.png" alt="">
            </h1>
            <p>
                Effortlessly reserve your study room anytime with our seamless online booking system, ensuring real-time availability and hassle-free scheduling!
            </p>
            <button class="discover" onclick="location.href='#studyRoom'">Discover Now</button>
        </div>
        <div class="introImage">
            <img src="/MedStudy-Space-System/User/images/icons/studyRoom.jpg" alt="">
        </div>
    </div>

    <div class="studyRooms" id="studyRoom">
        <h2>Study Rooms</h2>
        <?php 
        require_once '../php/get_rooms.php';
        
        // Debug information
        error_log("=== Home.php Room Display Debug ===");
        error_log("Number of rooms received: " . count($rooms));
        
        if (empty($rooms)): 
        ?>
            <p class="no-rooms">No study rooms available at the moment.</p>
        <?php else:
            // Create a copy of the rooms array to avoid reference issues
            $display_rooms = array_values($rooms);
            
            error_log("Number of rooms to display: " . count($display_rooms));
            
            foreach ($display_rooms as $index => $room): 
                // Skip if room ID is missing
                if (!isset($room['room_id'])) {
                    error_log("Error: Room at index $index missing ID - skipping");
                    continue;
                }
                
                error_log("Processing display for index $index - Room ID: {$room['room_id']}");
                
                // Set default values for display
                $roomName = htmlspecialchars($room['room_name'] ?? 'Unnamed Room');
                $status = htmlspecialchars($room['status'] ?? 'Unknown');
                $capacity = htmlspecialchars($room['student_capacity'] ?? '0');
                $chairs = htmlspecialchars($room['chairs'] ?? '0');
                $tables = htmlspecialchars($room['tables'] ?? '0');
                
                $roomImage = isset($room['room_image_base64']) && $room['room_image_base64'] 
                    ? 'data:image/png;base64,' . htmlspecialchars($room['room_image_base64'])
                    : '/MedStudy-Space-System/User/images/icons/studyRoom2.jpg';
                    
                $statusClass = strtolower(str_replace(' ', '-', $status));
                
                error_log("Displaying room - Name: $roomName, Status: $status");
        ?>
            <div class="room" data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>">
                <div class="roomImage">
                    <img src="<?php echo $roomImage; ?>" alt="<?php echo $roomName; ?>">
                </div>
                <div class="roomInfo">
                    <h3><?php echo $roomName; ?></h3>
                    <span class="room-status <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                    <div class="room-details">
                        <div class="detail-item">
                            <img src="/MedStudy-Space-System/User/images/icons/crowd-of-users.png" alt="">
                            <span><?php echo $capacity; ?> Students</span>
                        </div>
                        <div class="detail-item">
                            <img src="/MedStudy-Space-System/User/images/icons/swivel-chair.png" alt="">
                            <span><?php echo $chairs; ?> Chairs</span>
                        </div>
                        <div class="detail-item">
                            <img src="/MedStudy-Space-System/User/images/icons/table.png" alt="">
                            <span><?php echo $tables; ?> Tables</span>
                        </div>
                    </div>
                    <button onclick="openRoomOverlay(
                        <?php echo htmlspecialchars($room['room_id']); ?>, 
                        '<?php echo $roomName; ?>', 
                        '<?php echo $roomImage; ?>', 
                        '<?php echo $status; ?>', 
                        <?php echo $capacity; ?>, 
                        <?php echo $chairs; ?>, 
                        <?php echo $tables; ?>)" 
                        <?php echo $status !== 'Available' ? 'disabled' : ''; ?>>
                        Book Now
                    </button>
                </div>
            </div>
        <?php 
                error_log("Finished displaying room ID: {$room['room_id']}");
            endforeach;
            
            error_log("=== Display Summary ===");
            error_log("Total rooms displayed: " . count($display_rooms));
        endif; 
        ?>
    </div>

    <!-- Room Details Overlay -->
    <div class="roomOverlay" id="roomOverlay">
        <div class="roomBox">
            <img src="/MedStudy-Space-System/User/images/icons/left-arrow.png" id="backArrow" alt="" onclick="closeRoomOverlay()">
            <div class="roomBoxContainer">
                <div class="leftRoom">
                    <img class="ga" id="roomOverlayImage" src="/MedStudy-Space-System/User/images/icons/studyRoom2.jpg" alt="">
                    <h1 id="roomOverlayName">Study Room 1</h1>
                    <span id="roomOverlayStatus" class="room-status">Available</span>
                    <div class="roomBoxDescription">
                        <div class="leftRoomBoxDesc">
                            <div class="additionalInfo">
                                <img src="/MedStudy-Space-System/User/images/icons/crowd-of-users.png" alt="">
                                <span id="roomOverlayCapacity">5 Students</span>
                            </div>
                            <div class="additionalInfo">
                                <img src="/MedStudy-Space-System/User/images/icons/swivel-chair.png" alt="">
                                <span id="roomOverlayChairs">5 Chairs</span>
                            </div>
                            <div class="additionalInfo">
                                <img src="/MedStudy-Space-System/User/images/icons/table.png" alt="">
                                <span id="roomOverlayTables">1 Table</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rightRoom">
                    <h2>Available Dates</h2>
                    <div id="calendar"></div>
                    <div class="legendForCalendar">
                        <div class="legend">
                            <div class="legend-color" style="background-color: #014E6F;"></div>
                            <span>Your Booking</span>
                        </div>
                        <div class="legend">
                            <div class="legend-color" style="background-color: #FF4D4D;"></div>
                            <span>Cancelled</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Selection Overlay -->
    <div class="bookOverlay" id="bookOverlay">
        <div class="bookBox">
            <img src="/MedStudy-Space-System/User/images/icons/left-arrow.png" alt="" onclick="closeBookOverlay()">
            <div class="bookDescription">
                <h1>Study Room 1</h1>
                <span>Monday, March 24</span>
                <div class="setTime">
                    <h2>Select Starting Time</h2>
                    <input type="text" id="timePicker" placeholder="Select start time">
                    <h2>End Time</h2>
                    <input type="text" id="endTimePicker" placeholder="Select end time">
                    <p class="time-note">Note: Booking duration must be between 10 minutes and 2 hours</p>
                    <button onclick="openBookConfirmaationOverlay()">Book</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Confirmation Overlay -->
    <div class="bookConfirmationOverlay" id="bookConfirmationOverlay">
        <div class="bookConfirmationBox">
            <p>Do you want to book Study Room 1?</p>
            <div>
                <button onclick="closeBookConfirmationOverlay()">No</button>
                <button onclick="confirmBook()">Yes</button>
            </div>
        </div>
    </div>

    <!-- Booking Success Overlay -->
    <div class="bookConfirmedOverlay" id="bookConfirmedOverlay">
        <div class="bookConfirmedBox">
            <div class="topConfirmation">
                <div class="topLeftConfirmation">
                    <img src="/MedStudy-Space-System/User/images/icons/studyRoom2.jpg" alt="">
                </div>
                <div class="topRightConfirmation">
                    <h2>Booked</h2>
                    <p>Room QR Code</p>
                    <img src="/MedStudy-Space-System/User/images/icons/qr-code_Black.png" alt="">
                    <br>
                    <span>
                        Note: You can find this QR code on the door of the study room. Scan the QR code to open the door.
                    </span>
                </div>
            </div>
            <div class="bottomConfirmation">
                <div class="bottomLeftConfirmation">
                    <h1>Study Room 1</h1>
                    <div>
                        <img src="/MedStudy-Space-System/User/images/icons/crowd-of-users.png" alt="">
                        <span>5 Students</span>
                    </div>
                    <div>
                        <img src="/MedStudy-Space-System/User/images/icons/swivel-chair.png" alt="">
                        <span>5 Chairs</span>
                    </div>
                    <div>
                        <img src="/MedStudy-Space-System/User/images/icons/table.png" alt="">
                        <span>1 Table</span>
                    </div>
                </div>
                <div class="bottomRightConfirmation">
                    <p>
                        Monday - March 24, 2025 <br>
                        <span>8:00 AM - 10:00 AM</span>
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeConfirmedBook()">Back</button>
        </div>
    </div>

    <!-- Fully Booked Overlay -->
    <div class="fullyBookedOverlay" id="fullyBookedOverlay">
        <div class="fullyBookedBox">
            <img src="/MedStudy-Space-System/User/images/icons/information.png" alt="">
            <p>This date is fully booked.</p>
            <button onclick="closeFullyBookedOverlay()">Back</button>
        </div>
    </div>

    <!-- Not Available Overlay -->
    <div class="notAvailableOverlay" id="notAvailableOverlay">
        <div class="notAvailableBox">
            <img src="/MedStudy-Space-System/User/images/icons/warning.png" alt="">
            <p>This date is not available.</p>
            <button onclick="closeNotAvailableOverlay()">Back</button>
        </div>
    </div>

    <!-- Custom Alert Overlay -->
    <div class="customAlertOverlay" id="customAlertOverlay">
        <div class="customAlertBox">
            <div class="alertIcon">
                <img id="alertIcon" src="/MedStudy-Space-System/User/images/icons/information.png" alt="">
            </div>
            <p id="alertMessage"></p>
            <button onclick="closeCustomAlert()">OK</button>
        </div>
    </div>

    <!-- Cancelled Date Overlay -->
    <div class="cancelledDateOverlay" id="cancelledDateOverlay" style="display: none;">
        <div class="cancelledDateBox">
            <div class="overlay-icon">
                <img src="/MedStudy-Space-System/User/images/icons/warning.png" alt="Warning">
            </div>
            <h2>Booking Unavailable</h2>
            <p>This date has been cancelled and is not available for booking.</p>
            <div class="overlay-actions">
                <button onclick="closeCancelledDateOverlay()">Understood</button>
            </div>
        </div>
    </div>

    <!-- Duplicate Booking Overlay -->
    <div class="duplicateBookingOverlay" id="duplicateBookingOverlay" style="display: none;">
        <div class="duplicateBookingBox">
            <div class="overlay-icon">
                <img src="/MedStudy-Space-System/User/images/icons/warning.png" alt="Warning">
            </div>
            <h2>One Booking Per Day</h2>
            <p>You already have a booking on this date. To ensure fair access for all students, only one booking per day is allowed.</p>
            <div class="overlay-actions">
                <button onclick="closeDuplicateBookingOverlay()">I Understand</button>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="topFooter">
            <div class="medStudyLogo">
                <img class="mage" src="/MedStudy-Space-System/User/images/logos/whiteVer2.png" alt="">
                <h1>Study Smart, <br> Book Easy.</h1>
            </div>
            <div class="infoCol">
                <div><h2>Contact Us</h2></div>
                <p>
                    Tagum Unit, Apokon, 8100 Tagum City. <br>
                    (082) 227-8192 <br>
                    payment.tagummabini@usep.edu.ph
                </p>
            </div>
            <div class="infoCol">
                <div><h2>Helpful Links</h2></div>
                <a href="/MedStudy-Space-System/User/html/home.php">Home</a> <br>
                <a href="/MedStudy-Space-System/User/html/schedule.php">Schedule</a>
            </div>
            <div class="infoCol">
                <div><h2>About Us</h2></div>
                <p>
                    MedStudy System, developed by BSIT students in partnership with the SOM Library, streamlines study room reservations with easy booking, real-time scheduling, and fair access for medical students.
                </p>
            </div>
        </div>
        <div class="bottomFooter">
            <div class="socialMedia">
                <div class="icon">
                    <img src="/MedStudy-Space-System/User/images/icons/facebook-app-symbol.png" alt="">
                </div>
                <div class="icon">
                    <img src="/MedStudy-Space-System/User/images/icons/twitter.png" alt="">
                </div>
                <div class="icon">
                    <img src="/MedStudy-Space-System/User/images/icons/instagram.png" alt="">
                </div>
                <div class="icon">
                    <img src="/MedStudy-Space-System/User/images/icons/youtube.png" alt="">
                </div>
            </div>
            <br>
            <p>© 2025 University of Southeastern Philippines. All Rights Reserved.</p>
        </div>
    </div>

    <!-- Script for Overlays -->
    <script>
        // Global variables to store current room details
        let currentRoom = {
            id: null,
            name: '',
            image: '',
            status: '',
            capacity: 0,
            chairs: 0,
            tables: 0,
            qrCode: '',
            selectedDate: '',
            startTime: '',
            endTime: ''
        };

        function closeConfirmedBook() {
            const overlay = document.getElementById("bookConfirmedOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
                // Reset form fields
                document.getElementById("timePicker").value = "";
                document.getElementById("endTimePicker").value = "";
                // Restore body scroll
                document.body.style.overflow = "auto";
            }, 300);
        }

        // Function to fetch room details
        async function fetchRoomDetails(roomId) {
            try {
                const response = await fetch('/MedStudy-Space-System/User/php/get_room_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        room_id: roomId
                    })
                });
                const data = await response.json();
                if (data.success) {
                    return data.room;
                }
                throw new Error(data.message);
            } catch (error) {
                console.error('Error fetching room details:', error);
                return null;
            }
        }

        function openRoomOverlay(roomId, roomName, roomImage, status, capacity, chairs, tables) {
            // First, try to use the passed parameters
            currentRoom = {
                id: roomId,
                name: roomName,
                image: roomImage,
                status: status,
                capacity: capacity,
                chairs: chairs,
                tables: tables,
                qrCode: '',  // Will be set when booking is confirmed
                selectedDate: '',
                startTime: '',
                endTime: ''
            };

            // Set currentRoom globally for calendar
            window.currentRoom = currentRoom;

            // Update room overlay elements
            document.getElementById("roomOverlayImage").src = roomImage;
            document.getElementById("roomOverlayName").innerText = roomName;
            document.getElementById("roomOverlayStatus").innerText = status;
            
            // Update room details
            const capacityEl = document.getElementById("roomOverlayCapacity");
            const chairsEl = document.getElementById("roomOverlayChairs");
            const tablesEl = document.getElementById("roomOverlayTables");
            
            if (capacityEl) capacityEl.innerText = `${capacity} Students`;
            if (chairsEl) chairsEl.innerText = `${chairs} Chairs`;
            if (tablesEl) tablesEl.innerText = `${tables} Tables`;
            
            // Fetch additional room details if needed
            fetchRoomDetails(roomId).then(roomDetails => {
                if (roomDetails) {
                    // Update with fetched details if different
                    if (roomDetails.capacity !== capacity) {
                        capacityEl.innerText = `${roomDetails.capacity} Students`;
                    }
                    if (roomDetails.chairs !== chairs) {
                        chairsEl.innerText = `${roomDetails.chairs} Chairs`;
                    }
                    if (roomDetails.tables !== tables) {
                        tablesEl.innerText = `${roomDetails.tables} Tables`;
                    }
                }
            });
            
            // Show the overlay
            const overlay = document.getElementById("roomOverlay");
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";

            // Initialize calendar and load bookings immediately
            if (window.myCalendar) {
                const currentDate = new Date();
                const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
                
                loadBookings(
                    roomId,
                    firstDay.toISOString().split('T')[0],
                    lastDay.toISOString().split('T')[0]
                ).then(bookings => {
                    updateCalendarCells(bookings);
                });
            }
        }

        function closeRoomOverlay() {
            const overlay = document.getElementById("roomOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
            }, 300);
            document.body.style.overflow = "auto";
        }

        function openBookOverlay(date) {
            currentRoom.selectedDate = date;
            const overlay = document.getElementById("bookOverlay");
            const bookTitle = overlay.querySelector(".bookDescription h1");
            const bookDate = overlay.querySelector(".bookDescription span");
            
            bookTitle.innerText = currentRoom.name;
            bookDate.innerText = new Date(date).toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
        }

        function closeBookOverlay() {
            const overlay = document.getElementById("bookOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
            }, 300);
            document.body.style.overflow = "auto";
        }

        function openBookConfirmaationOverlay() {
            const startTime = document.getElementById("timePicker").value;
            const endTime = document.getElementById("endTimePicker").value;
            
            if (!startTime || !endTime) {
                showCustomAlert("Please select both start and end times", "error");
                return;
            }

            currentRoom.startTime = startTime;
            currentRoom.endTime = endTime;

            const overlay = document.getElementById("bookConfirmationOverlay");
            const message = overlay.querySelector("p");
            message.innerHTML = `Do you want to book <strong>${currentRoom.name}</strong><br>
                               on ${new Date(currentRoom.selectedDate).toLocaleDateString('en-US', { 
                                   weekday: 'long', 
                                   month: 'long', 
                                   day: 'numeric',
                                   year: 'numeric'
                               })}<br>
                               from ${startTime} to ${endTime}?`;
            
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
        }

        function closeBookConfirmationOverlay() {
            const overlay = document.getElementById("bookConfirmationOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
            }, 300);
            document.body.style.overflow = "auto";
        }

        // Function to load bookings for the calendar
        async function loadBookings(roomId, startDate, endDate) {
            try {
                const response = await fetch('/MedStudy-Space-System/User/php/get_bookings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        room_id: roomId,
                        start_date: startDate,
                        end_date: endDate
                    })
                });
                const data = await response.json();
                if (data.success) {
                    return data.bookings;
                }
                throw new Error(data.message);
            } catch (error) {
                console.error('Error loading bookings:', error);
                return [];
            }
        }

        // Function to handle booking submission
        async function confirmBook() {
            const startTime = document.getElementById("timePicker").value;
            const endTime = document.getElementById("endTimePicker").value;
            
            if (!startTime || !endTime) {
                showCustomAlert("Please select both start and end times", "error");
                return;
            }

            try {
                const bookingDate = new Date(currentRoom.selectedDate);
                const utcDate = new Date(Date.UTC(
                    bookingDate.getFullYear(),
                    bookingDate.getMonth(),
                    bookingDate.getDate()
                )).toISOString().split('T')[0];

                const response = await fetch('/MedStudy-Space-System/User/php/process_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        room_id: currentRoom.id,
                        booking_date: utcDate,
                        start_time: startTime,
                        end_time: endTime
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Close overlays
                    closeBookConfirmationOverlay();
                    closeBookOverlay();
                    closeRoomOverlay();
                    
                    // Show success overlay
                    const overlay = document.getElementById("bookConfirmedOverlay");
                    updateBookingConfirmationDetails(startTime, endTime);
                    
                    overlay.style.display = "flex";
                    setTimeout(() => {
                        overlay.classList.add("active");
                    }, 10);
                    document.body.style.overflow = "hidden";
                    
                    // Refresh calendar
                    if (window.myCalendar) {
                        window.myCalendar.refetchEvents();
                    }
                } else {
                    closeBookConfirmationOverlay();
                    
                    // Handle duplicate booking error
                    if (data.error_type === 'duplicate_booking') {
                        openDuplicateBookingOverlay();
                    } else {
                        showCustomAlert(data.message || 'Booking failed. Please try again.', 'error');
                    }
                }
            } catch (error) {
                console.error('Error processing booking:', error);
                showCustomAlert('An error occurred while processing your booking. Please try again.', 'error');
            }
        }

        function updateBookingConfirmationDetails(startTime, endTime) {
            const overlay = document.getElementById("bookConfirmedOverlay");
            
            // Update room image and details
            overlay.querySelector(".topLeftConfirmation img").src = currentRoom.image;
            overlay.querySelector(".bottomLeftConfirmation h1").innerText = currentRoom.name;
            overlay.querySelector(".bottomLeftConfirmation div:nth-child(2) span").innerText = `${currentRoom.capacity} Students`;
            overlay.querySelector(".bottomLeftConfirmation div:nth-child(3) span").innerText = `${currentRoom.chairs} Chairs`;
            overlay.querySelector(".bottomLeftConfirmation div:nth-child(4) span").innerText = `${currentRoom.tables} Tables`;
            
            // Update booking time details
            const dateStr = new Date(currentRoom.selectedDate).toLocaleDateString('en-US', {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });
            overlay.querySelector(".bottomRightConfirmation p").innerHTML = 
                `${dateStr}<br><span>${startTime} - ${endTime}</span>`;
        }

        function openFullyBookedOverlay() {
            const overlay = document.getElementById("fullyBookedOverlay");
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
        }

        function closeFullyBookedOverlay() {
            const overlay = document.getElementById("fullyBookedOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
            }, 300);
            document.body.style.overflow = "auto";
        }

        function openNotAvailableOverlay() {
            const overlay = document.getElementById("notAvailableOverlay");
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
        }

        function closeNotAvailableOverlay() {
            const overlay = document.getElementById("notAvailableOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
            }, 300);
            document.body.style.overflow = "auto";
        }

        function openProfileDetailsOverlay() {
            const overlay = document.getElementById("profileDetailsOverlay");
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
        }

        function closeProfileDetailsOverlay() {
            const overlay = document.getElementById("profileDetailsOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
            }, 300);
            document.body.style.overflow = "auto";
        }

        function uploadImage() {
            document.getElementById("imageUpload").click();
        }

        function previewImage(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            if (file && file.type.startsWith("image/")) {
                reader.onload = function(e) {
                    document.getElementById("profileImage").src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                alert("Please upload a valid image file (PNG, JPG, JPEG).");
            }
        }

        function saveProfileDetails() {
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const contactNumber = document.getElementById('contactNumber').value;
            const fileInput = document.getElementById('imageUpload');
            const formData = new FormData();
            formData.append('first_name', firstName);
            formData.append('last_name', lastName);
            formData.append('contact_number', contactNumber);
            if (fileInput.files[0]) {
                formData.append('profile_pic', fileInput.files[0]);
            }

            fetch('/MedStudy-Space-System/User/php/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showLoading('/MedStudy-Space-System/User/html/home.php');
                } else {
                    alert('Failed to update profile: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error updating profile: ' + error);
            });
        }

        // Enhanced calendar marking function
        function updateCalendarCells(userBookings) {
            // Ensure userBookings is an array
            if (!Array.isArray(userBookings)) {
                console.error('Invalid bookings data:', userBookings);
                // Try to use previously stored bookings
                userBookings = window.lastLoadedBookings || [];
            }

            // Store bookings for future reference
            window.lastLoadedBookings = userBookings;

            // Create a map of bookings by date for easier lookup
            const bookingsByDate = {};
            userBookings.forEach(booking => {
                if (booking && booking.date) {
                    // Ensure the booking is for the current room
                    if (window.currentRoom && booking.room_id == window.currentRoom.id) {
                        bookingsByDate[booking.date] = booking;
                    }
                }
            });

            // Select all calendar day elements
            const dateEls = document.querySelectorAll('.fc-daygrid-day');
            dateEls.forEach(el => {
                const dateNumberEl = el.querySelector('.fc-daygrid-day-number');
                const date = el.getAttribute('data-date');
                
                // Reset styles
                if (dateNumberEl) {
                    dateNumberEl.style.color = '';
                    dateNumberEl.style.fontWeight = '';
                    dateNumberEl.style.transform = '';
                    
                    // Add hover effect
                    dateNumberEl.classList.add('booking-date-number');
                }
                
                // Remove existing classes
                el.classList.remove(
                    'fc-day-user-booking', 
                    'fc-day-cancelled', 
                    'fc-day-past'
                );

                // Check if this date has a booking for the current room
                const booking = bookingsByDate[date];
                if (booking) {
                    // Determine marking based on status
                    switch(booking.status) {
                        case 'cancelled':
                            el.classList.add('fc-day-cancelled');
                            if (dateNumberEl) {
                                dateNumberEl.style.color = '#FF4D4D';
                                dateNumberEl.style.fontWeight = 'bold';
                                dateNumberEl.classList.add('cancelled-booking');
                            }
                            break;
                        case 'user-booking':
                            el.classList.add('fc-day-user-booking');
                            if (dateNumberEl) {
                                dateNumberEl.style.color = '#014E6F';
                                dateNumberEl.style.fontWeight = 'bold';
                                dateNumberEl.style.transform = 'scale(1.1)';
                                dateNumberEl.classList.add('user-booking');
                            }
                            break;
                    }
                }
            });
        }

        // Add custom CSS for hover effects
        const hoverStyleEl = document.createElement('style');
        hoverStyleEl.textContent = `
            .booking-date-number {
                transition: all 0.3s ease;
                padding: 4px;
                border-radius: 50%;
                display: inline-block;
                position: relative;
                z-index: 10;
            }

            .booking-date-number:hover {
                background-color: rgba(1, 78, 111, 0.1);
                transform: scale(1.2) !important;
                cursor: pointer;
            }

            .booking-date-number.user-booking:hover {
                background-color: rgba(1, 78, 111, 0.2);
            }

            .booking-date-number.cancelled-booking:hover {
                background-color: rgba(255, 77, 77, 0.2);
            }

            .fc-day-past .booking-date-number:hover {
                background-color: rgba(173, 181, 189, 0.1);
                cursor: not-allowed;
            }
        `;
        document.head.appendChild(hoverStyleEl);

        function openDuplicateBookingOverlay() {
            const overlay = document.getElementById("duplicateBookingOverlay");
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
        }

        function closeDuplicateBookingOverlay() {
            const overlay = document.getElementById("duplicateBookingOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
                closeBookOverlay();
            }, 300);
            document.body.style.overflow = "auto";
        }

        // Function to open cancelled date overlay
        function openCancelledDateOverlay() {
            const overlay = document.getElementById("cancelledDateOverlay");
            if (overlay) {
                overlay.style.display = "flex";
                setTimeout(() => {
                    overlay.classList.add("active");
                }, 10);
                document.body.style.overflow = "hidden";
            }
        }

        // Function to close cancelled date overlay
        function closeCancelledDateOverlay() {
            const overlay = document.getElementById("cancelledDateOverlay");
            if (overlay) {
                overlay.classList.remove("active");
                setTimeout(() => {
                    overlay.style.display = "none";
                }, 300);
                document.body.style.overflow = "auto";
            }
        }
    </script>

    <!-- For time input -->
    <script>
        flatpickr("#timePicker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i K",
            time_24hr: false,
            minTime: "08:00",
            maxTime: "16:50",
            minuteIncrement: 10,
            defaultDate: "08:00",
            allowInput: false,
            onChange: function(selectedDates, dateStr, instance) {
                const startTime = selectedDates[0];
                const endPicker = document.querySelector("#endTimePicker")._flatpickr;
                
                const minEnd = new Date(startTime);
                minEnd.setMinutes(minEnd.getMinutes() + 10);
                
                const maxEnd = new Date(startTime);
                maxEnd.setHours(maxEnd.getHours() + 2);
                const fivePM = new Date(startTime);
                fivePM.setHours(17, 0, 0);
                
                const effectiveMaxEnd = maxEnd < fivePM ? maxEnd : fivePM;
                
                endPicker.set("minTime", minEnd.toTimeString().slice(0, 5));
                endPicker.set("maxTime", effectiveMaxEnd.toTimeString().slice(0, 5));
                
                const currentEnd = endPicker.selectedDates[0];
                if (currentEnd < minEnd || currentEnd > effectiveMaxEnd) {
                    endPicker.setDate(minEnd);
                }
            }
        });

        flatpickr("#endTimePicker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i K",
            time_24hr: false,
            minTime: "08:10",
            maxTime: "17:00",
            minuteIncrement: 10,
            defaultDate: "10:00",
            allowInput: false,
            onChange: function(selectedDates, dateStr, instance) {
                const endTime = selectedDates[0];
                const startTime = document.querySelector("#timePicker")._flatpickr.selectedDates[0];
                
                const duration = (endTime - startTime) / (1000 * 60);
                if (duration < 10 || duration > 120) {
                    showCustomAlert("Booking duration must be between 10 minutes and 2 hours", "error");
                    instance.setDate(new Date(startTime.getTime() + 10 * 60000));
                }
            }
        });
    </script>

    <!-- For Scrolling Behaviour -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (window.location.hash === "#studyRoom") {
                var studyRoomsSection = document.getElementById("studyRoom");
                if (studyRoomsSection) {
                    setTimeout(() => {
                        studyRoomsSection.scrollIntoView({ behavior: "smooth" });
                    }, 500);
                }
            }
        });
    </script>

    <!-- Calendar Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            if (!calendarEl) {
                console.error("Calendar element not found");
                return;
            }

            // Define business hours (Monday-Friday, 8am-5pm)
            const businessHours = {
                daysOfWeek: [1, 2, 3, 4, 5], // Monday-Friday
                startTime: '08:00',
                endTime: '17:00'
            };

            function createCalendar() {
                // Destroy existing calendar if it exists
                if (window.myCalendar) {
                    window.myCalendar.destroy();
                }

                // Clear the calendar element
                calendarEl.innerHTML = '';

                // Create new calendar instance
                window.myCalendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: "dayGridMonth",
                    height: "auto",
                    contentHeight: "auto",
                    expandRows: true,
                    fixedWeekCount: false,
                    showNonCurrentDates: true,
                    businessHours: businessHours,
                    selectConstraint: "businessHours",
                    headerToolbar: {
                        left: "prev,next today",
                        center: "title",
                        right: ""
                    },
                    datesSet: function(info) {
                        // Ensure current room is set
                        if (window.currentRoom && window.currentRoom.id) {
                            // Extend the date range to ensure full coverage
                            const extendedStart = new Date(info.start);
                            extendedStart.setDate(extendedStart.getDate() - 7);
                            
                            const extendedEnd = new Date(info.end);
                            extendedEnd.setDate(extendedEnd.getDate() + 7);
                            
                            loadBookings(
                                window.currentRoom.id,
                                extendedStart.toISOString().split('T')[0],
                                extendedEnd.toISOString().split('T')[0]
                            ).then(bookings => {
                                // Always update calendar cells after loading bookings
                                window.lastLoadedBookings = bookings;
                                updateCalendarCells(bookings);
                            }).catch(error => {
                                console.error('Error loading bookings:', error);
                                // Fallback to previously loaded bookings if available
                                if (window.lastLoadedBookings) {
                                    updateCalendarCells(window.lastLoadedBookings);
                                }
                            });
                        }
                    },
                    viewDidMount: function(info) {
                        // Ensure markings persist after view changes
                        if (window.currentRoom && window.currentRoom.id) {
                            // Extend the date range to ensure full coverage
                            const extendedStart = new Date(info.view.currentStart);
                            extendedStart.setDate(extendedStart.getDate() - 7);
                            
                            const extendedEnd = new Date(info.view.currentEnd);
                            extendedEnd.setDate(extendedEnd.getDate() + 7);
                            
                            loadBookings(
                                window.currentRoom.id,
                                extendedStart.toISOString().split('T')[0],
                                extendedEnd.toISOString().split('T')[0]
                            ).then(bookings => {
                                // Store bookings for persistent marking
                                window.lastLoadedBookings = bookings;
                                updateCalendarCells(bookings);
                            }).catch(error => {
                                console.error('Error loading bookings:', error);
                                // Fallback to previously loaded bookings if available
                                if (window.lastLoadedBookings) {
                                    updateCalendarCells(window.lastLoadedBookings);
                                }
                            });
                        }
                    },
                    dateClick: function(info) {
                        const date = info.date;
                        const isBusinessDay = date.getDay() >= 1 && date.getDay() <= 5;
                        const now = new Date();
                        now.setHours(0, 0, 0, 0);
                        
                        if (!isBusinessDay || date < now) {
                            openNotAvailableOverlay();
                            return;
                        }

                        if (!window.currentRoom || !window.currentRoom.id) {
                            console.error('No room selected');
                            return;
                        }

                        // Always open book overlay for business days
                        window.currentRoom.selectedDate = info.dateStr;
                        openBookOverlay(date);
                    }
                });

                // Render calendar
                window.myCalendar.render();
            }

            // Override the openRoomOverlay function
            const originalOpenRoomOverlay = window.openRoomOverlay;
            window.openRoomOverlay = function(roomId, roomName, roomImage, status, capacity, chairs, tables) {
                // Call the original function first
                if (typeof originalOpenRoomOverlay === 'function') {
                    originalOpenRoomOverlay(roomId, roomName, roomImage, status, capacity, chairs, tables);
                } else {
                    currentRoom = {
                        id: roomId,
                        name: roomName,
                        image: roomImage,
                        status: status,
                        capacity: capacity,
                        chairs: chairs,
                        tables: tables,
                        qrCode: '',
                        selectedDate: '',
                        startTime: '',
                        endTime: ''
                    };

                    // Set currentRoom globally for calendar
                    window.currentRoom = currentRoom;

                    // Update room overlay elements
                    document.getElementById("roomOverlayImage").src = roomImage;
                    document.getElementById("roomOverlayName").innerText = roomName;
                    document.getElementById("roomOverlayStatus").innerText = status;
                    
                    // Update room details
                    const capacityEl = document.getElementById("roomOverlayCapacity");
                    const chairsEl = document.getElementById("roomOverlayChairs");
                    const tablesEl = document.getElementById("roomOverlayTables");
                    
                    if (capacityEl) capacityEl.innerText = `${capacity} Students`;
                    if (chairsEl) chairsEl.innerText = `${chairs} Chairs`;
                    if (tablesEl) tablesEl.innerText = `${tables} Tables`;
                    
                    // Show the overlay
                    const overlay = document.getElementById("roomOverlay");
                    overlay.style.display = "flex";
                    setTimeout(() => {
                        overlay.classList.add("active");
                    }, 10);
                    document.body.style.overflow = "hidden";
                }

                // Initialize or reinitialize calendar after overlay is shown
                setTimeout(() => {
                    createCalendar();
                    
                    // Load initial bookings with multiple attempts
                    function loadInitialBookings(attempts = 3) {
                        if (currentRoom && currentRoom.id) {
                            const currentDate = new Date();
                            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
                            
                            loadBookings(
                                currentRoom.id,
                                firstDay.toISOString().split('T')[0],
                                lastDay.toISOString().split('T')[0]
                            ).then(bookings => {
                                if (window.myCalendar) {
                                    updateCalendarCells(bookings);
                                }
                            }).catch(error => {
                                console.error('Error loading initial bookings:', error);
                                
                                // Retry with decreasing attempts
                                if (attempts > 0) {
                                    setTimeout(() => {
                                        loadInitialBookings(attempts - 1);
                                    }, 500);
                                }
                            });
                        }
                    }

                    loadInitialBookings();
                }, 300); // Wait for overlay transition
            };
        });
    </script>

    <style>
    /* Base overlay styles */
    .roomOverlay,
    .bookOverlay,
    .bookConfirmationOverlay,
    .bookConfirmedOverlay,
    .fullyBookedOverlay,
    .notAvailableOverlay,
    .customAlertOverlay,
    .duplicateBookingOverlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .roomOverlay.active,
    .bookOverlay.active,
    .bookConfirmationOverlay.active,
    .bookConfirmedOverlay.active,
    .fullyBookedOverlay.active,
    .notAvailableOverlay.active,
    .customAlertOverlay.active,
    .duplicateBookingOverlay.active {
        opacity: 1;
    }

    /* Calendar Container Styles */
    #calendar {
        width: 100%;
        height: 100%;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    /* Enhanced Calendar Styling */
    .fc {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        height: auto !important;
        background: white;
    }

    .fc .fc-toolbar {
        padding: 15px 0;
    }

    .fc .fc-toolbar-title {
        color: #014E6F;
        font-size: 1.5em;
        font-weight: 600;
        text-transform: capitalize;
    }

    .fc .fc-button {
        background-color: #52BBBF;
        border-color: #52BBBF;
        text-transform: capitalize;
        font-weight: 500;
        padding: 8px 16px;
        transition: all 0.3s ease;
    }

    .fc .fc-button:hover {
        background-color: #014E6F;
        border-color: #014E6F;
    }

    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background-color: #014E6F;
        border-color: #014E6F;
    }

    .fc .fc-col-header {
        background-color: #f8f9fa;
    }

    .fc .fc-col-header-cell {
        padding: 12px 0;
        color: #014E6F;
        font-weight: 600;
        font-size: 0.95em;
    }

    .fc .fc-daygrid-day {
        min-height: 80px;
        transition: all 0.3s ease;
        position: relative;
        border: 1px solid #f1f3f5;
    }

    .fc .fc-daygrid-day-number {
        position: absolute;
        top: 8px;
        right: 8px;
        font-size: 0.9em;
        color: #868e96;
        transition: all 0.3s ease;
    }

    /* Day Number Styling */
    .fc .day-number {
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-block;
        padding: 5px;
        border-radius: 50%;
    }

    .fc .day-number:hover {
        background-color: rgba(1, 78, 111, 0.1);
        transform: scale(1.1);
    }

    /* Calendar Day Styles - Ensure No Background Color */
    .fc .fc-daygrid-day {
        background-color: transparent !important;
    }

    .fc .fc-day-user-booking,
    .fc .fc-day-cancelled,
    .fc .fc-day-past,
    .fc .fc-day-today,
    .fc .fc-day-sat,
    .fc .fc-day-sun {
        background-color: transparent !important;
    }

    /* Day Number Styling */
    .fc .fc-day-user-booking .fc-daygrid-day-number {
        color: #014E6F !important;
        font-weight: bold;
        transform: scale(1.2);
    }

    .fc .fc-day-cancelled .fc-daygrid-day-number {
        color: #FF4D4D !important;
        font-weight: bold;
    }

    .fc .fc-day-past .fc-daygrid-day-number {
        color: #ADB5BD !important;
        text-decoration: line-through;
    }

    /* Legend Styling */
    .legendForCalendar {
        margin-top: 15px;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        gap: 20px;
    }

    .legend {
        display: flex;
        align-items: center;
        margin: 0;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        margin-right: 8px;
    }

    .legend span {
        font-size: 0.9em;
        color: #495057;
        font-weight: 500;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .fc .fc-toolbar {
            flex-direction: column;
            align-items: center;
        }

        .fc .fc-toolbar-chunk {
            margin-bottom: 10px;
        }

        .fc .fc-daygrid-day {
            min-height: 60px;
        }
    }

    /* Professional Overlay Styles */
    .cancelledDateOverlay,
    .duplicateBookingOverlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .cancelledDateOverlay.active,
    .duplicateBookingOverlay.active {
        display: flex;
        opacity: 1;
    }

    .cancelledDateBox,
    .duplicateBookingBox {
        background-color: #ffffff;
        border-radius: 16px;
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        max-width: 450px;
        width: 90%;
        padding: 30px;
        text-align: center;
        position: relative;
        transform: scale(0.7);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .cancelledDateOverlay.active .cancelledDateBox,
    .duplicateBookingOverlay.active .duplicateBookingBox {
        transform: scale(1);
        opacity: 1;
    }

    .overlay-icon {
        width: 100px;
        height: 100px;
        background-color: #FFE5E5;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 20px;
        animation: pulse 1.5s infinite;
    }

    .overlay-icon img {
        width: 50px;
        height: 50px;
        object-fit: contain;
    }

    .cancelledDateBox h2,
    .duplicateBookingBox h2 {
        color: #FF4D4D;
        font-size: 1.8em;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .cancelledDateBox p,
    .duplicateBookingBox p {
        color: #495057;
        font-size: 1em;
        line-height: 1.6;
        margin-bottom: 25px;
    }

    .overlay-actions {
        display: flex;
        justify-content: center;
    }

    .overlay-actions button {
        background-color: #014E6F;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .overlay-actions button:hover {
        background-color: #052c44;
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            transform: scale(1);
        }
    }

    /* Responsive Adjustments */
    @media (max-width: 480px) {
        .cancelledDateBox,
        .duplicateBookingBox {
            width: 95%;
            padding: 20px;
        }

        .overlay-icon {
            width: 80px;
            height: 80px;
        }

        .overlay-icon img {
            width: 40px;
            height: 40px;
        }

        .cancelledDateBox h2,
        .duplicateBookingBox h2 {
            font-size: 1.5em;
        }

        .overlay-actions button {
            padding: 10px 25px;
            font-size: 0.9em;
        }
    }

    /* Time Picker Styles */
    .flatpickr-input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1em;
        color: #495057;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
    }

    .flatpickr-input:focus {
        border-color: #014E6F;
        box-shadow: 0 0 0 0.2rem rgba(1, 78, 111, 0.25);
        outline: none;
    }

    .flatpickr-time input {
        font-size: 1em;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        text-align: center;
    }


    .setTime h2 {
        color: #014E6F;
        margin-bottom: 15px;
        font-size: 1.2em;
    }

    .setTime .time-note {
        color: #6c757d;
        font-size: 0.9em;
        margin-top: 10px;
        font-style: italic;
    }

    .setTime button {
        width: 100%;
        padding: 12px;
        background-color: #014E6F;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1em;
        font-weight: 600;
        margin-top: 15px;
        transition: all 0.3s ease;
    }

    .setTime button:hover {
        background-color: #052c44;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Book Overlay Styles */
    .bookOverlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .bookOverlay.active {
        display: flex;
        opacity: 1;
    }

    .bookBox {
        background-color: white;
        border-radius: 16px;
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        max-width: 500px;
        width: 90%;
        padding: 30px;
        position: relative;
        transform: scale(0.7);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .bookOverlay.active .bookBox {
        transform: scale(1);
        opacity: 1;
    }

    .bookBox img {
        position: absolute;
        top: 20px;
        left: 20px;
        width: 30px;
        height: 30px;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .bookBox img:hover {
        transform: scale(1.1);
    }

    .bookDescription {
        text-align: center;
    }

    .bookDescription h1 {
        color: #014E6F;
        margin-bottom: 10px;
    }

    .bookDescription span {
        color: #6c757d;
        font-size: 1em;
        display: block;
        margin-bottom: 20px;
    }
    </style>

    <script>
    function showCustomAlert(message, type = 'info') {
        const overlay = document.getElementById('customAlertOverlay');
        const messageEl = document.getElementById('alertMessage');
        const iconEl = document.getElementById('alertIcon');
        
        // Set icon based on type
        switch(type) {
            case 'error':
                iconEl.src = '/MedStudy-Space-System/User/images/icons/warning.png';
                break;
            case 'success':
                iconEl.src = '/MedStudy-Space-System/User/images/icons/check.png';
                break;
            default:
                iconEl.src = '/MedStudy-Space-System/User/images/icons/information.png';
        }
        
        messageEl.innerHTML = message;
        overlay.style.display = 'flex';
        setTimeout(() => {
            overlay.classList.add('active');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeCustomAlert() {
        const overlay = document.getElementById('customAlertOverlay');
        overlay.classList.remove('active');
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 300);
        document.body.style.overflow = 'auto';
    }
    </script>
</body>
</html>