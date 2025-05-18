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
$stmt = $conn->prepare("SELECT ud.first_name, ud.last_name, ud.middle_name, ud.contact_number, ud.profile_pic 
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
$middle_name = $user['middle_name'] ?? $_SESSION['middle_name'] ?? '';
$contact_number = $user['contact_number'] ?? '';
$profile_pic = $user['profile_pic'] ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : '/MedStudy-Space-System/User/images/icons/black.jpg';
$middle_initial = $middle_name ? substr($middle_name, 0, 1) . '.' : '';
$full_name = $first_name . ' ' . $middle_initial . ($middle_initial ? ' ' : '') . $last_name;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedStudy | Schedule</title>
    <link rel="icon" href="/MedStudy-Space-System/User/images/logos/medstudyLogo.png">
    <link rel="stylesheet" href="/MedStudy-Space-System/User/css/schedule.css">
    <link rel="stylesheet" href="/MedStudy-Space-System/User/css/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.10/index.min.css" />
    <script src="/MedStudy-Space-System/User/js/linkActive.js"></script>
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
                <a href="/MedStudy-Space-System/User/html/home.php" class="link">Home</a>
                <a href="/MedStudy-Space-System/User/html/schedule.php" class="link active">Schedule</a>
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
    <div class="scheduling">
        <div class="profileContainer">
            <div class="profile">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" onclick="openProfileDetailsOverlay()">
                <p><?php echo htmlspecialchars($full_name); ?></p>
                <h2>Student Check-in</h2>
            </div>
            <div class="bookingInformation" id="bookingInformation">
                <div class="studyRoomInfo">
                    <h1>Study Room 1</h1>
                    <div class="roomDescription">
                        <img src="/MedStudy-Space-System/User/images/icons/crowd-of-users.png" alt="">
                        <span>5 students</span>
                    </div>
                    <div class="roomDescription">
                        <img src="/MedStudy-Space-System/User/images/icons/swivel-chair.png" alt="">
                        <span>5 chairs</span>
                    </div>
                    <div class="roomDescription">
                        <img src="/MedStudy-Space-System/User/images/icons/table.png" alt="">
                        <span>1 table</span>
                    </div>
                </div>
                <div class="scanRoom" onclick="location.href='/MedStudy-Space-System/User/html/scanner.html'">
                    <img src="/MedStudy-Space-System/User/images/icons/scanner.png" alt="">
                    <span>
                        <p>
                            <span><b>Scan Your Room</b></span><br>
                            Note: Scan the QR code of the Study Room to unlock the door. Access is only granted during your reserved time.
                        </p>
                    </span>
                </div>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="calendarContainer">
            <h1>Scheduled Calendar</h1>
            <div class="calendarBody">
                <img src="/MedStudy-Space-System/User/images/icons/notification.png" alt="" onclick="openNotificationOverlay()">
                <div id="calendar"></div>
                <div class="legendForCalendar">
                    <div class="legend">
                        <img src="/MedStudy-Space-System/User/images/icons/legendOccupied.png" alt="">
                        <span>Your Schedule</span>
                    </div>
                    <div class="legend">
                        <img src="/MedStudy-Space-System/User/images/icons/legendUnnoccupied.png" alt="">
                        <span>Occupied</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Container -->
        <div class="bookingContainer">
            <div class="bookingDetails" id="bookingDetails">
                <img src="/MedStudy-Space-System/User/images/icons/studyRoom2.jpg" alt="">
                <h1>Study Room 1</h1>
                <p>March 24, 2025: 8:00 AM - 10:00 AM</p> <br>
                <button onclick="openBookCancelConfirmationOverlay()">Cancel Book</button>
            </div>
            <div class="noBooking" id="noBooking">
                <img src="/MedStudy-Space-System/User/images/icons/information2.png" alt="">
                <p>You haven't book yet.</p>
                <button onclick="location.href='/MedStudy-Space-System/User/html/home.php#studyRoom'">Book a Room</button>
            </div>
        </div>
    </div>

    <!-- Profile Details Overlay -->
    <div class="profileDetailsOverlay" id="profileDetailsOverlay">
        <div class="profileDetailsBox">
            <h2>Account Details</h2>
            <div class="profileDetailsContainer">
                <div class="leftProfileDetails">
                    <img id="profileImage" src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
                    <div class="uploadPhoto" onclick="uploadImage()">
                        <img src="/MedStudy-Space-System/User/images/icons/image-.png" alt="" style="height: 1%;">
                        <span>Upload New Photo</span>
                    </div>
                    <input type="file" id="imageUpload" accept="image/png, image/jpeg, image/jpg" style="display: none;" onchange="previewImage(event)">
                </div>
                <div class="rightProfileDetails">
                    <div class="inputGroup">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" value="<?php echo htmlspecialchars($first_name); ?>">
                    </div>
                    <div class="inputGroup">
                        <label for="middleName">Middle Name</label>
                        <input type="text" id="middleName" value="<?php echo htmlspecialchars($middle_name); ?>">
                    </div>
                    <div class="inputGroup">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" value="<?php echo htmlspecialchars($last_name); ?>">
                    </div>
                    <div class="inputGroup">
                        <label for="contactNumber">Contact Number</label>
                        <input type="text" id="contactNumber" value="<?php echo htmlspecialchars($contact_number); ?>">
                    </div>
                    <div class="inputGroup">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                    </div>
                </div>
            </div>
            <div class="profileDetailsButtons">
                <button onclick="closeProfileDetailsOverlay()">Cancel</button>
                <button onclick="saveProfileDetails()">Save</button>
            </div>
        </div>
    </div>

    <!-- Notification Overlay -->
    <div class="notificationOverlay" id="notificationOverlay">
        <div class="notificationBox">
            <img src="/MedStudy-Space-System/User/images/icons/close.png" alt="" onclick="closeNotificationOverlay()">
            <div class="notificationContainer">
                <h2>Notification</h2>
                <div class="notificationSection">
                    <div class="notifPopUps">
                        <img src="/MedStudy-Space-System/User/images/icons/notification.png" alt="">
                        <span>
                            <b>Booked Successfully</b> <br>
                            You've successfully booked Study Room 1.
                        </span>
                    </div>
                    <div class="notifPopUps">
                        <img src="/MedStudy-Space-System/User/images/icons/warningNotif.png" alt="">
                        <span>
                            <b>Booking Has Been Void</b> <br>
                            You did not show up in the booked date.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Overlay -->
    <div class="bookCancelConfirmationOverlay" id="bookCancelConfirmationOverlay">
        <div class="bookCancelConfirmationBox">
            <img src="/MedStudy-Space-System/User/images/icons/warning.png" alt="">
            <h2>Cancel Booking Confirmation</h2>
            <div id="cancelBookingDetails"></div>
            <p>Are you sure you want to cancel this booking?</p>
            <div class="confirmation-buttons">
                <button class="cancel-btn" onclick="closeBookCancelConfirmationOverlay()">No, Keep it</button>
                <button class="confirm-btn" onclick="cancelBooking()">Yes, Cancel it</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="topFooter">
            <div class="medStudyLogo">
                <img src="/MedStudy-Space-System/User/images/logos/whiteVer2.png" alt="">
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
                    The University of Southeastern Philippines (USeP), a public, research, coeducational, regional state university based in Davao City, was established in 1978 by integrating four state educational institutions.
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

    <!-- Calendar Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.10/index.global.min.js"></script>
    <script>
        let calendarInitialized = false;
        let currentCalendarInstance = null;
        let currentBookings = {};
        let selectedBooking = null;

        // Helper Functions
        async function loadUserBookingsAndAvailability(start, end) {
            try {
                const formData = new FormData();
                formData.append('start_date', start);
                formData.append('end_date', end);

                const response = await fetch('/MedStudy-Space-System/User/php/get_user_bookings.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                if (data.success) {
                    currentBookings = data.user_bookings;
                    return data;
                }
                throw new Error(data.message || 'Failed to load bookings');
            } catch (error) {
                console.error('Error loading bookings:', error);
                return null;
            }
        }

        function formatTime(timeStr) {
            return new Date('2000-01-01T' + timeStr).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function updateBookingDisplay(bookings, selectedDate = null) {
            const bookingDetails = document.getElementById("bookingDetails");
            const noBooking = document.getElementById("noBooking");
            const bookingInfo = document.getElementById("bookingInformation");

            if (!bookings || Object.keys(bookings).length === 0) {
                if (bookingDetails) bookingDetails.style.display = "none";
                if (bookingInfo) bookingInfo.style.display = "none";
                if (noBooking) noBooking.style.display = "block";
                return;
            }

            // Get the booking to display (either selected date or most recent)
            const dateToShow = selectedDate || Object.keys(bookings)[0];
            const bookingToShow = bookings[dateToShow][0];
            selectedBooking = { date: dateToShow, ...bookingToShow };

            // Update booking details in the right panel
            if (bookingDetails) {
                bookingDetails.style.display = "block";
                bookingDetails.querySelector("h1").textContent = bookingToShow.room_name;
                bookingDetails.querySelector("p").textContent = 
                    `${formatDate(dateToShow)}: ${formatTime(bookingToShow.start_time)} - ${formatTime(bookingToShow.end_time)}`;
                
                // Update the room image if it exists in the booking data
                const roomImage = bookingDetails.querySelector("img");
                if (roomImage && bookingToShow.room_image) {
                    roomImage.src = bookingToShow.room_image;
                } else {
                    roomImage.src = "/MedStudy-Space-System/User/images/icons/studyRoom2.jpg";
                }
            }

            // Update booking information in the left panel
            if (bookingInfo) {
                bookingInfo.style.display = "block";
                const studyRoomInfo = bookingInfo.querySelector(".studyRoomInfo");
                if (studyRoomInfo) {
                    // Update room name
                    const roomName = studyRoomInfo.querySelector("h1");
                    if (roomName) {
                        roomName.textContent = bookingToShow.room_name;
                    }

                    // Update room details
                    const roomDetails = studyRoomInfo.querySelectorAll(".roomDescription");
                    if (roomDetails.length >= 3) {
                        // Update student capacity
                        if (bookingToShow.student_capacity) {
                            roomDetails[0].querySelector("span").textContent = `${bookingToShow.student_capacity} students`;
                        }
                        // Update chairs
                        if (bookingToShow.chairs) {
                            roomDetails[1].querySelector("span").textContent = `${bookingToShow.chairs} chairs`;
                        }
                        // Update tables
                        if (bookingToShow.tables) {
                            roomDetails[2].querySelector("span").textContent = `${bookingToShow.tables} tables`;
                        }
                    }
                }

                // Update QR code section if available
                const scanRoom = bookingInfo.querySelector(".scanRoom");
                if (scanRoom && bookingToShow.qr_code) {
                    const qrImage = scanRoom.querySelector("img");
                    if (qrImage) {
                        qrImage.src = bookingToShow.qr_code;
                    }
                }
            }

            if (noBooking) {
                noBooking.style.display = "none";
            }
        }

        function updateCalendarCells(userBookings, roomAvailability) {
            document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
                const date = cell.getAttribute('data-date');
                const cellDate = new Date(date);
                const now = new Date();
                now.setHours(0, 0, 0, 0);
                
                // Remove any existing status classes
                cell.classList.remove('fc-day-user-booking', 'fc-day-fully-booked', 'fc-day-available');
                
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

                // Check user bookings first
                if (userBookings && userBookings[date]) {
                    cell.classList.add('fc-day-user-booking');
                    cell.querySelector('.fc-daygrid-day-number').style.color = '#ffffff';
                    cell.querySelector('.fc-daygrid-day-number').style.fontWeight = 'bold';
                }
                // Then check room availability
                else if (roomAvailability && roomAvailability[date]) {
                    const availability = roomAvailability[date];
                    if (availability.status === 'fully_booked') {
                        cell.classList.add('fc-day-fully-booked');
                    } else {
                        cell.classList.add('fc-day-available');
                    }
                }
            });
        }

        async function cancelBooking() {
            if (!selectedBooking) {
                showCustomAlert("No booking selected to cancel", "error");
                return;
            }

            try {
                const response = await fetch('/MedStudy-Space-System/User/php/cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        room_id: selectedBooking.room_id,
                        booking_date: selectedBooking.date,
                        start_time: selectedBooking.start_time,
                        end_time: selectedBooking.end_time
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // Remove the cancelled booking from currentBookings
                    delete currentBookings[selectedBooking.date];
                    
                    // Close the confirmation overlay first
                    closeBookCancelConfirmationOverlay();

                    // Update displays
                    if (Object.keys(currentBookings).length === 0) {
                        const bookingDetails = document.getElementById("bookingDetails");
                        const bookingInfo = document.getElementById("bookingInformation");
                        const noBooking = document.getElementById("noBooking");
                        
                        if (bookingDetails) bookingDetails.style.display = "none";
                        if (bookingInfo) bookingInfo.style.display = "none";
                        if (noBooking) noBooking.style.display = "block";
                    } else {
                        updateBookingDisplay(currentBookings);
                    }

                    // Completely reinitialize calendar
                    if (currentCalendarInstance) {
                        const calendarEl = document.getElementById("calendar");
                        if (calendarEl) {
                            currentCalendarInstance.destroy();
                            calendarInitialized = false;
                            setTimeout(() => {
                                initializeCalendar();
                                
                                // Fetch and update data for the current month
                                const currentDate = new Date();
                                const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                                const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
                                
                                loadUserBookingsAndAvailability(
                                    firstDay.toISOString().split('T')[0],
                                    lastDay.toISOString().split('T')[0]
                                ).then(data => {
                                    if (data) {
                                        updateCalendarCells(data.user_bookings, data.room_availability);
                                    }
                                });
                            }, 100);
                        }
                    }

                    showCustomAlert("Booking cancelled successfully", "success");
                } else {
                    throw new Error(data.message || 'Failed to cancel booking');
                }
            } catch (error) {
                console.error('Error cancelling booking:', error);
                showCustomAlert("Failed to cancel booking: " + error.message, "error");
            }
        }

        function closeBookCancelConfirmationOverlay() {
            const overlay = document.getElementById("bookCancelConfirmationOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
                document.body.style.overflow = "auto";
            }, 300);
        }

        function initializeCalendar() {
            const calendarEl = document.getElementById("calendar");
            if (!calendarEl) {
                console.error("Calendar element not found");
                return null;
            }

            // Destroy existing calendar if it exists
            if (currentCalendarInstance) {
                currentCalendarInstance.destroy();
                currentCalendarInstance = null;
            }

            // Clear the calendar element
            calendarEl.innerHTML = '';

            try {
                // Create new calendar instance
                currentCalendarInstance = new FullCalendar.Calendar(calendarEl, {
                    initialView: "dayGridMonth",
                    height: "auto",
                    contentHeight: "auto",
                    expandRows: true,
                    fixedWeekCount: false,
                    headerToolbar: {
                        left: "prev,next today",
                        center: "title",
                        right: ""
                    },
                    datesSet: async function(info) {
                        const data = await loadUserBookingsAndAvailability(
                            info.start.toISOString().split('T')[0],
                            info.end.toISOString().split('T')[0]
                        );

                        if (data) {
                            updateBookingDisplay(data.user_bookings);
                            updateCalendarCells(data.user_bookings, data.room_availability);
                        }
                    },
                    dateClick: function(info) {
                        const date = info.dateStr;
                        const cell = info.dayEl;
                        
                        if (currentBookings && currentBookings[date]) {
                            updateBookingDisplay(currentBookings, date);
                            
                            // Add highlight effect
                            cell.style.transition = 'background-color 0.3s ease';
                            cell.style.backgroundColor = 'rgba(1, 78, 111, 0.1)';
                            setTimeout(() => {
                                cell.style.backgroundColor = '';
                            }, 300);
                        }
                    }
                });

                // Add hover effects for tooltips
                const tooltip = document.createElement('div');
                tooltip.className = 'fc-day-tooltip';
                document.body.appendChild(tooltip);

                calendarEl.addEventListener('mouseover', (e) => {
                    const cell = e.target.closest('.fc-daygrid-day');
                    if (!cell) return;

                    const date = cell.getAttribute('data-date');
                    if (currentBookings && currentBookings[date]) {
                        const booking = currentBookings[date][0];
                        tooltip.textContent = `${booking.room_name}: ${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}`;
                        tooltip.style.left = e.pageX + 10 + 'px';
                        tooltip.style.top = e.pageY + 10 + 'px';
                        tooltip.classList.add('show');
                    }
                });

                calendarEl.addEventListener('mouseout', () => {
                    tooltip.classList.remove('show');
                });

                // Render calendar
                currentCalendarInstance.render();
                calendarInitialized = true;
                return currentCalendarInstance;
            } catch (error) {
                console.error("Error initializing calendar:", error);
                return null;
            }
        }

        // Initialize calendar when DOM is loaded
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(() => {
                const calendar = initializeCalendar();
                if (!calendar) {
                    console.error("Failed to initialize calendar");
                }
            }, 100);
        });
    </script>

    <!-- Calendar Styles -->
    <style>
        /* Calendar Container */
        #calendar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        /* Calendar Header */
        .fc .fc-toolbar {
            margin-bottom: 1.5em;
        }

        .fc .fc-toolbar-title {
            font-size: 1.5em;
            color: #014E6F;
        }

        .fc .fc-button-primary {
            background-color: #52BBBF;
            border-color: #52BBBF;
        }

        .fc .fc-button-primary:hover {
            background-color: #014E6F;
            border-color: #014E6F;
        }

        /* Calendar Days */
        .fc .fc-daygrid-day {
            min-height: 100px;
            transition: all 0.3s ease;
        }

        .fc .fc-day-user-booking {
            position: relative;
            background-color: #ffffff !important;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .fc .fc-day-user-booking:hover {
            transform: scale(1.02);
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
            transition: all 0.3s ease;
        }

        .fc .fc-day-user-booking:hover::after {
            box-shadow: 0 0 15px rgba(1, 78, 111, 0.5);
            transform: translate(-50%, -50%) scale(1.1);
        }

        .fc .fc-day-fully-booked {
            position: relative;
            background-color: #ffffff !important;
            cursor: not-allowed;
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

        .fc .fc-day-available {
            position: relative;
            background-color: #ffffff !important;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .fc .fc-day-available:hover {
            transform: scale(1.02);
        }

        .fc .fc-day-available::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 32px;
            height: 32px;
            background-color: #52BBBF;
            opacity: 0.15;
            border-radius: 50%;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .fc .fc-day-available:hover::after {
            opacity: 0.3;
            transform: translate(-50%, -50%) scale(1.1);
        }

        /* Calendar Tooltip */
        .fc-day-tooltip {
            position: absolute;
            background-color: rgba(1, 78, 111, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 1000;
            pointer-events: none;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .fc-day-tooltip.show {
            opacity: 1;
        }
    </style>

    <div class="popup" id="loadingPopup">
        <div class="popup-content">
            <p>Please wait for a moment<br><br>Saving....</p>
            <img src="/MedStudy-Space-System/User/images/logos/medstudyLogo.png" alt="Loading" class="loading-image">
        </div>
    </div>
    <script>
        function showLoading(redirectUrl) {
            document.getElementById('loadingPopup').style.display = 'flex';
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 3000);
            closeProfileDetailsOverlay();
        }

        function saveProfileDetails() {
            const firstName = document.getElementById('firstName').value;
            const middleName = document.getElementById('middleName').value;
            const lastName = document.getElementById('lastName').value;
            const contactNumber = document.getElementById('contactNumber').value;
            const fileInput = document.getElementById('imageUpload');
            const formData = new FormData();
            formData.append('first_name', firstName);
            formData.append('middle_name', middleName);
            formData.append('last_name', lastName);
            formData.append('contact_number', contactNumber);
            if (fileInput.files[0]) {
                formData.append('profile_pic', fileInput.files[0]);
                console.log('File selected:', fileInput.files[0].name, fileInput.files[0].type, fileInput.files[0].size);
            } else {
                console.log('No file selected');
            }

            fetch('/MedStudy-Space-System/User/php/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('HTTP error ' + response.status + ': ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showLoading('/MedStudy-Space-System/User/html/schedule.php');
                } else {
                    alert('Failed to update profile: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Error updating profile: ' + error.message);
            });
        }

        function openNotificationOverlay() {
            const overlay = document.getElementById("notificationOverlay");
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
        }
        function closeNotificationOverlay() {
            const overlay = document.getElementById("notificationOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
            }, 300);
            document.body.style.overflow = "auto";
        }

        function openBookCancelConfirmationOverlay() {
            if (!selectedBooking) {
                showCustomAlert("No booking selected to cancel", "error");
                return;
            }

            const overlay = document.getElementById("bookCancelConfirmationOverlay");
            const detailsContainer = document.getElementById("cancelBookingDetails");
            
            // Update the cancellation details
            detailsContainer.innerHTML = `
                <p><strong>Room:</strong> ${selectedBooking.room_name}</p>
                <p><strong>Date:</strong> ${formatDate(selectedBooking.date)}</p>
                <p><strong>Time:</strong> ${formatTime(selectedBooking.start_time)} - ${formatTime(selectedBooking.end_time)}</p>
            `;

            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
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
                    console.log('Image preview updated:', e.target.result.substring(0, 50) + '...');
                };
                reader.readAsDataURL(file);
            } else {
                alert("Please upload a valid image file (PNG, JPG, JPEG).");
            }
        }
    </script>
</body>
</html>