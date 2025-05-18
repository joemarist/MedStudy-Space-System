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
                            <div class="legend-color" style="background-color: #52BBBF; border-radius: 50%; width: 20px; height: 20px; display: inline-block;"></div>
                            <span>Vacant (8:00 AM - 5:00 PM)</span>
                        </div>
                        <div class="legend">
                            <div class="legend-color" style="background-color: #E6F3FF; border-radius: 50%; width: 20px; height: 20px; display: inline-block;"></div>
                            <span>Fully Booked</span>
                        </div>
                        <div class="legend">
                            <div class="legend-color" style="background-color: #014E6F; border-radius: 50%; width: 20px; height: 20px; display: inline-block;"></div>
                            <span>Your Booking</span>
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

    <!-- Duplicate Booking Overlay -->
    <div class="duplicateBookingOverlay" id="duplicateBookingOverlay">
        <div class="duplicateBookingBox">
            <img src="/MedStudy-Space-System/User/images/icons/warning.png" alt="">
            <h2>One Booking Per Day</h2>
            <p>You already have a booking on this date. To ensure fair access for all students, only one booking per day is allowed.</p>
            <button onclick="closeDuplicateBookingOverlay()">I Understand</button>
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

        function openRoomOverlay(roomId, roomName, roomImage, status, capacity, chairs, tables) {
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

            // Update room overlay elements
            document.getElementById("roomOverlayImage").src = roomImage;
            document.getElementById("roomOverlayName").innerText = roomName;
            document.getElementById("roomOverlayStatus").innerText = status;
            
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

                console.log('Sending booking request:', {
                    room_id: currentRoom.id,
                    booking_date: utcDate,
                    start_time: startTime,
                    end_time: endTime
                });

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
                console.log('Booking response:', data);

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

        // Function to update calendar cell styles
        function updateCalendarCells(bookings) {
            document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
                const date = cell.getAttribute('data-date');
                const cellDate = new Date(date);
                const now = new Date();
                now.setHours(0, 0, 0, 0);
                
                // Remove any existing status classes
                cell.classList.remove('fc-day-vacant', 'fc-day-user-booking', 'fc-day-fully-booked');
                
                // Past dates
                if (cellDate < now) {
                    cell.classList.add('fc-day-past');
                    return;
                }
                
                // Weekends
                const day = cellDate.getDay();
                if (day === 0 || day === 6) {
                    return;
                }
                
                const booking = bookings.find(b => b.date === date);
                if (booking) {
                    switch(booking.status) {
                        case 'user-booking':
                            cell.classList.add('fc-day-user-booking');
                            cell.querySelector('.fc-daygrid-day-number').style.color = '#ffffff';
                            cell.querySelector('.fc-daygrid-day-number').style.fontWeight = 'bold';
                            cell.querySelector('.fc-daygrid-day-number').style.position = 'relative';
                            cell.querySelector('.fc-daygrid-day-number').style.zIndex = '2';
                            break;
                        case 'fully-booked':
                            cell.classList.add('fc-day-fully-booked');
                            cell.querySelector('.fc-daygrid-day-number').style.color = '#014E6F';
                            cell.querySelector('.fc-daygrid-day-number').style.position = 'relative';
                            cell.querySelector('.fc-daygrid-day-number').style.zIndex = '2';
                            break;
                        case 'vacant':
                            cell.classList.add('fc-day-vacant');
                            cell.querySelector('.fc-daygrid-day-number').style.color = '#014E6F';
                            cell.querySelector('.fc-daygrid-day-number').style.position = 'relative';
                            cell.querySelector('.fc-daygrid-day-number').style.zIndex = '2';
                            break;
                    }
                }
            });
        }

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
        let calendarInitialized = false;

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
                    datesSet: async function(info) {
                        if (currentRoom && currentRoom.id) {
                            try {
                                const bookings = await loadBookings(
                                    currentRoom.id,
                                    info.start.toISOString().split('T')[0],
                                    info.end.toISOString().split('T')[0]
                                );
                                updateCalendarCells(bookings);
                            } catch (error) {
                                console.error('Error loading bookings:', error);
                            }
                        }
                    },
                    dateClick: async function(info) {
                        const date = info.date;
                        const isBusinessDay = date.getDay() >= 1 && date.getDay() <= 5;
                        const now = new Date();
                        now.setHours(0, 0, 0, 0);
                        
                        if (!isBusinessDay || date < now) {
                            openNotAvailableOverlay();
                            return;
                        }

                        if (!currentRoom || !currentRoom.id) {
                            console.error('No room selected');
                            return;
                        }

                        try {
                            const bookings = await loadBookings(
                                currentRoom.id,
                                info.dateStr,
                                info.dateStr
                            );

                            const booking = bookings.find(b => b.date === info.dateStr);
                            if (booking && booking.status === 'fully-booked') {
                                openFullyBookedOverlay();
                            } else {
                                currentRoom.selectedDate = info.dateStr;
                                openBookOverlay(date);
                            }
                        } catch (error) {
                            console.error('Error checking date availability:', error);
                            showCustomAlert('Error checking date availability. Please try again.', 'error');
                        }
                    }
                });

                // Render calendar
                window.myCalendar.render();
                calendarInitialized = true;
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

                    // Update room overlay elements
                    document.getElementById("roomOverlayImage").src = roomImage;
                    document.getElementById("roomOverlayName").innerText = roomName;
                    document.getElementById("roomOverlayStatus").innerText = status;
                    
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
                    
                    // Load initial bookings
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
                        });
                    }
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
        transition: all 0.2s ease;
        cursor: pointer;
        min-height: 100px;
    }

    .fc .fc-daygrid-day:hover {
        background-color: #f8f9fa;
    }

    .fc .fc-daygrid-day-number {
        padding: 8px;
        color: #495057;
        font-weight: 500;
    }

    /* Calendar cell status styles */
    .fc .fc-day-vacant {
        position: relative;
        background-color: #ffffff !important;
    }

    .fc .fc-day-vacant::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 32px;
        height: 32px;
        background-color: #52BBBF;
        border-radius: 50%;
        opacity: 0.15;
        z-index: 1;
    }

    .fc .fc-day-user-booking {
        position: relative;
        background-color: #ffffff !important;
    }

    .fc .fc-day-user-booking::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 32px;
        height: 32px;
        background-color: #014E6F;
        border-radius: 50%;
        z-index: 1;
    }

    .fc .fc-day-fully-booked {
        position: relative;
        background-color: #ffffff !important;
    }

    .fc .fc-day-fully-booked::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 32px;
        height: 32px;
        background-color: #E6F3FF;
        border: 2px solid #014E6F;
        border-radius: 50%;
        z-index: 1;
    }

    .fc .fc-day-past {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }

    .fc .fc-day-today {
        background-color: #e8f4f4 !important;
    }

    .fc .fc-day-today .fc-daygrid-day-number {
        color: #014E6F;
        font-weight: bold;
    }

    /* Weekend days */
    .fc .fc-day-sat, .fc .fc-day-sun {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }

    .fc .fc-day-sat .fc-daygrid-day-number,
    .fc .fc-day-sun .fc-daygrid-day-number {
        color: #adb5bd;
    }

    /* Enhanced legend styling */
    .legendForCalendar {
        margin-top: 20px;
        padding: 15px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .legend {
        display: flex;
        align-items: center;
        margin: 8px 0;
    }

    .legend-color {
        margin-right: 10px;
        border: 2px solid transparent;
    }

    .legend span {
        color: #495057;
        font-size: 0.9em;
    }

    /* Custom Alert Overlay */
    .customAlertBox {
        background-color: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        max-width: 400px;
        width: 90%;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }

    .customAlertOverlay.active .customAlertBox {
        transform: scale(1);
    }

    .alertIcon {
        margin-bottom: 20px;
    }

    .alertIcon img {
        width: 50px;
        height: 50px;
    }

    .customAlertBox p {
        margin: 20px 0;
        color: #333;
        font-size: 16px;
        line-height: 1.5;
    }

    .customAlertBox button {
        background-color: #52BBBF;
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }

    .customAlertBox button:hover {
        background-color: #014E6F;
    }

    /* Duplicate Booking Overlay */
    .duplicateBookingBox {
        background-color: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        max-width: 450px;
        width: 90%;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }

    .duplicateBookingOverlay.active .duplicateBookingBox {
        transform: scale(1);
    }

    .duplicateBookingBox img {
        width: 60px;
        height: 60px;
        margin-bottom: 20px;
    }

    .duplicateBookingBox h2 {
        color: #014E6F;
        margin-bottom: 15px;
        font-size: 24px;
    }

    .duplicateBookingBox p {
        margin: 20px 0;
        color: #333;
        font-size: 16px;
        line-height: 1.5;
    }

    .duplicateBookingBox button {
        background-color: #52BBBF;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }

    .duplicateBookingBox button:hover {
        background-color: #014E6F;
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