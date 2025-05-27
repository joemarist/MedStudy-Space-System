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
$stmt = $conn->prepare("SELECT ud.first_name, ud.last_name, ud.middle_name, ud.contact_number, ud.profile_pic, ua.user_id 
                       FROM user_details ud 
                       JOIN user_accounts ua ON ud.user_id = ua.user_id 
                       WHERE ua.email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Ensure user_id is set in the session
if (!isset($_SESSION['user_id']) && $user) {
    $_SESSION['user_id'] = $user['user_id'];
}

// Log user details for debugging
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'Not Set'));
error_log("Fetched User ID: " . ($user['user_id'] ?? 'Not Found'));
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
                <div class="scanRoom" onclick="navigateToScanner()">
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
                        <img src="/MedStudy-Space-System/User/images/icons/legendCancelled.png" alt="">
                        <span>Cancelled</span>
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
                    <button id="changePassBT" class="changePassBT">Change Password</button>
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
        let currentBookingId = null;

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

            // Filter bookings to include only in-process and cancelled bookings
            const filteredBookings = {};
            Object.entries(bookings).forEach(([date, dateBookings]) => {
                const activeDateBookings = dateBookings.filter(
                    booking => booking.status === 'in_process' || 
                               booking.status === 'cancelled by admin' || 
                               booking.status === 'cancelled by student'
                );
                
                if (activeDateBookings.length > 0) {
                    filteredBookings[date] = activeDateBookings;
                }
            });

            if (!filteredBookings || Object.keys(filteredBookings).length === 0) {
                if (bookingDetails) bookingDetails.style.display = "none";
                if (bookingInfo) bookingInfo.style.display = "none";
                if (noBooking) noBooking.style.display = "block";
                return;
            }

            // Prioritize in_process bookings
            let dateToShow = selectedDate;
            if (!dateToShow) {
                // Find the first date with an in_process booking
                const inProcessDates = Object.entries(filteredBookings)
                    .filter(([_, bookings]) => 
                        bookings.some(booking => booking.status === 'in_process')
                    )
                    .map(([date, _]) => date);

                dateToShow = inProcessDates.length > 0 
                    ? inProcessDates[0] 
                    : Object.keys(filteredBookings)[0];
            }

            const bookingsOnDate = filteredBookings[dateToShow];
            const bookingToShow = bookingsOnDate.find(
                booking => booking.status === 'in_process'
            ) || bookingsOnDate[0];

            selectedBooking = { date: dateToShow, ...bookingToShow };

            // Determine if the booking is cancelled
            const isCancelled = bookingToShow.status === 'cancelled by admin' || 
                                bookingToShow.status === 'cancelled by student';

            // Store the active booking in sessionStorage for scanner navigation
            if (!isCancelled) {
                sessionStorage.setItem('activeBooking', JSON.stringify({
                    book_id: bookingToShow.book_id,
                    room_id: bookingToShow.room_id,
                    booking_date: dateToShow,
                    start_time: bookingToShow.start_time,
                    end_time: bookingToShow.end_time,
                    room_name: bookingToShow.room_name
                }));
            }

            // Update booking details in the right panel
            if (bookingDetails) {
                bookingDetails.style.display = "block";
                bookingDetails.querySelector("h1").textContent = bookingToShow.room_name;
                
                // Modify display based on booking status
                if (isCancelled) {
                    bookingDetails.querySelector("p").innerHTML = 
                        `<span style="color: #FF4D4D;">Cancelled Booking</span><br>` +
                        `${formatDate(dateToShow)}: ${formatTime(bookingToShow.start_time)} - ${formatTime(bookingToShow.end_time)}`;
                    
                    // Change cancel button to show details
                    const cancelButton = bookingDetails.querySelector("button");
                    cancelButton.textContent = "View Cancellation Details";
                    cancelButton.onclick = () => showCancelledBookingDetails(bookingToShow);
                } else {
                    bookingDetails.querySelector("p").textContent = 
                        `${formatDate(dateToShow)}: ${formatTime(bookingToShow.start_time)} - ${formatTime(bookingToShow.end_time)}`;
                    
                    // Restore original cancel button functionality
                    const cancelButton = bookingDetails.querySelector("button");
                    cancelButton.textContent = "Cancel Book";
                    
                    // Log the booking details for debugging
                    console.log('Booking Details for Cancellation:', {
                        book_id: bookingToShow.book_id,
                        room_name: bookingToShow.room_name,
                        booking_date: dateToShow,
                        start_time: bookingToShow.start_time,
                        end_time: bookingToShow.end_time
                    });
                    
                    // Ensure book_id is passed correctly
                    cancelButton.onclick = () => {
                        const bookId = bookingToShow.book_id;
                        console.log('Attempting to Open Cancel Confirmation with Book ID:', bookId, typeof bookId);
                        openBookCancelConfirmationOverlay(
                            bookId, 
                            bookingToShow.room_name, 
                            dateToShow, 
                            bookingToShow.start_time, 
                            bookingToShow.end_time
                        );
                    };
                }
                
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
                bookingInfo.style.display = isCancelled ? "none" : "block";
                
                if (!isCancelled) {
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
            }

            if (noBooking) {
                noBooking.style.display = "none";
            }

            // Highlight the corresponding calendar cell
            if (currentCalendarInstance) {
                const cellElement = document.querySelector(`.fc-day[data-date="${dateToShow}"]`);
                if (cellElement) {
                    cellElement.classList.add('fc-day-selected');
                }
            }
        }

        function updateCalendarCells(userBookings, roomAvailability) {
            document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
                const date = cell.getAttribute('data-date');
                const cellDate = new Date(date);
                const now = new Date();
                now.setHours(0, 0, 0, 0);
                
                // Remove existing status classes
                cell.classList.remove(
                    'fc-day-user-booking', 
                    'fc-day-fully-booked', 
                    'fc-day-available', 
                    'fc-day-cancelled'
                );
                
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

                // Check room availability and bookings
                if (userBookings && userBookings[date]) {
                    const bookingsOnDate = userBookings[date];
                    
                    // Check booking status
                    const hasInProcessBooking = bookingsOnDate.some(
                        booking => booking.status === 'in_process'
                    );
                    
                    const hasCancelledBooking = bookingsOnDate.some(
                        booking => booking.status === 'cancelled by admin' || 
                                   booking.status === 'cancelled by student'
                    );

                    // Prioritize status marking
                    if (hasInProcessBooking) {
                        cell.classList.add('fc-day-user-booking');
                    } else if (hasCancelledBooking) {
                        cell.classList.add('fc-day-cancelled');
                    } else {
                        cell.classList.add('fc-day-available');
                    }
                } else {
                    // If no bookings for this user on this date
                    cell.classList.add('fc-day-available');
                }
            });
        }

        async function cancelBooking(explicitBookId = null) {
            try {
                // Determine the booking ID to use
                const bookId = explicitBookId || currentBookingId;
                
                console.log('Attempting to Cancel Booking:', {
                    explicitBookId: explicitBookId,
                    currentBookingId: currentBookingId,
                    finalBookId: bookId
                });

                // Validate booking ID
                if (!bookId) {
                    throw new Error('No booking selected for cancellation');
                }

                // Disable cancel button to prevent multiple submissions
                const cancelButton = document.querySelector('.bookCancelConfirmationBox button[onclick^="cancelBooking"]');
                const cancelNoButton = document.querySelector('.bookCancelConfirmationBox button[onclick="closeBookCancelConfirmationOverlay()"]');
                
                if (cancelButton) {
                    cancelButton.disabled = true;
                    cancelButton.textContent = 'Cancelling...';
                }
                
                if (cancelNoButton) {
                    cancelNoButton.disabled = true;
                }

                // Immediately close the confirmation overlay
                closeBookCancelConfirmationOverlay();

                // Create FormData to send booking ID
                const formData = new FormData();
                formData.append('book_id', bookId);

                // Log the form data being sent
                console.log('Sending FormData:', {
                    book_id: bookId,
                    type: typeof bookId
                });

                const response = await fetch('/MedStudy-Space-System/User/php/cancel_booking.php', {
                    method: 'POST',
                    body: formData
                });

                // Check if response is OK
                if (!response.ok) {
                    // Log the response status and text for debugging
                    const errorText = await response.text();
                    console.error('HTTP error! status:', response.status, 'text:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}, text: ${errorText}`);
                }

                // Attempt to parse JSON, with detailed error handling
                let data;
                const responseText = await response.text();
                console.log('Raw Response Text:', responseText);

                try {
                    data = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('JSON parsing error:', jsonError);
                    console.error('Response Text that failed to parse:', responseText);
                    throw new Error(`Invalid server response: ${responseText}`);
                }

                // Log the full response data for debugging
                console.log('Booking Cancellation Response:', data);

                // Check for success with more flexible validation
                if (data && (data.success === true || data.success === 'true')) {
                    // Refresh the calendar and booking display
                    if (currentCalendarInstance) {
                        currentCalendarInstance.refetchEvents();
                    }

                    // Reload the page to ensure complete refresh
                    location.reload();

                    // Show success alert
                    showCustomAlert("Booking cancelled successfully", "success");
                } else {
                    // Throw error with server-provided message
                    throw new Error(data.message || 'Failed to cancel booking');
                }
            } catch (error) {
                console.error('Error cancelling booking:', error);
                
                // Show user-friendly error
                showCustomAlert(
                    error.message || "Failed to cancel booking. Please try again or contact support.", 
                    "error"
                );
            } finally {
                // Reset booking ID and restore button states
                currentBookingId = null;
                const cancelButton = document.querySelector('.bookCancelConfirmationBox button[onclick^="cancelBooking"]');
                const cancelNoButton = document.querySelector('.bookCancelConfirmationBox button[onclick="closeBookCancelConfirmationOverlay()"]');
                
                if (cancelButton) {
                    cancelButton.disabled = false;
                    cancelButton.textContent = 'Yes';
                }
                
                if (cancelNoButton) {
                    cancelNoButton.disabled = false;
                }
            }
        }

        function openBookCancelConfirmationOverlay(bookId, roomName, bookingDate, startTime, endTime) {
            // Close any existing overlays first
            const existingOverlays = document.querySelectorAll('.bookCancelConfirmationOverlay');
            existingOverlays.forEach(overlay => {
                overlay.remove();
            });

            // Ensure currentBookingId is set globally and correctly
            currentBookingId = bookId;
            console.log('Opening Cancel Confirmation - Booking ID:', currentBookingId, typeof currentBookingId);
            
            // Create a new overlay dynamically
            const overlay = document.createElement('div');
            overlay.className = 'bookCancelConfirmationOverlay';
            overlay.id = 'bookCancelConfirmationOverlay';
            overlay.innerHTML = `
                <div class="bookCancelConfirmationBox" data-book-id="${bookId}">
                    <img src="/MedStudy-Space-System/User/images/icons/warning.png" alt="">
                    <h2>Cancel Booking Confirmation</h2>
                    <div id="cancelBookingDetails">
                        <p><strong>Room:</strong> ${roomName}</p>
                        <p><strong>Date:</strong> ${formatDate(bookingDate)}</p>
                        <p><strong>Time:</strong> ${formatTime(startTime)} - ${formatTime(endTime)}</p>
                    </div>
                    <p>Are you sure you want to cancel this booking?</p>
                    <div class="confirmation-buttons">
                        <button class="cancel-btn" onclick="closeBookCancelConfirmationOverlay()">No, Keep it</button>
                        <button class="confirm-btn" onclick="cancelBooking(${bookId})">Yes, Cancel it</button>
                    </div>
                </div>
            `;

            // Add the overlay to the body
            document.body.appendChild(overlay);
            
            // Show the overlay
            overlay.style.display = 'flex';
            setTimeout(() => {
                overlay.classList.add('active');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeBookCancelConfirmationOverlay() {
            const overlay = document.getElementById('bookCancelConfirmationOverlay');
            if (overlay) {
                overlay.classList.remove('active');
                setTimeout(() => {
                    overlay.remove();
                    document.body.style.overflow = 'auto';
                }, 300);
            }
            currentBookingId = null;
        }

        function initializeCalendar() {
            const calendarEl = document.getElementById("calendar");
            if (!calendarEl) {
                console.error("Calendar element not found");
                return null;
            }

            try {
                currentCalendarInstance = new FullCalendar.Calendar(calendarEl, {
                    initialView: "dayGridMonth",
                    height: "auto",
                    contentHeight: "auto",
                    expandRows: false,
                    fixedWeekCount: true,
                    showNonCurrentDates: true,
                    headerToolbar: {
                        left: "prev,next today",
                        center: "title",
                        right: ""
                    },
                    dayHeaderFormat: { weekday: 'short' },
                    dayCellContent: function(arg) {
                        // Only show the day number
                        return {
                            html: `<span class="day-number">${arg.date.getDate()}</span>`
                        };
                    },
                    eventContent: function(arg) {
                        // Return an empty object to prevent event text from showing
                        return { html: '' };
                    },
                    datesSet: async function(info) {
                        const data = await loadUserBookingsAndAvailability(
                            info.start.toISOString().split('T')[0],
                            info.end.toISOString().split('T')[0]
                        );

                        if (data) {
                            // Filter and categorize bookings
                            const filteredBookings = {};
                            Object.entries(data.user_bookings).forEach(([date, bookings]) => {
                                const activeDateBookings = bookings.filter(
                                    booking => booking.status === 'in_process' || 
                                               booking.status === 'cancelled by admin' || 
                                               booking.status === 'cancelled by student'
                                );
                                
                                if (activeDateBookings.length > 0) {
                                    filteredBookings[date] = activeDateBookings;
                                }
                            });

                            // Find the first date with an in_process booking
                            const inProcessDates = Object.entries(filteredBookings)
                                .filter(([_, bookings]) => 
                                    bookings.some(booking => booking.status === 'in_process')
                                )
                                .map(([date, _]) => date);

                            const dateToShow = inProcessDates.length > 0 
                                ? inProcessDates[0] 
                                : (Object.keys(filteredBookings).length > 0 ? Object.keys(filteredBookings)[0] : null);

                            updateBookingDisplay(filteredBookings, dateToShow);
                            updateCalendarCells(filteredBookings, data.room_availability);
                        }
                    },
                    dateClick: function(info) {
                        const date = info.dateStr;
                        const bookingsOnDate = currentBookings[date] || [];
                        
                        // Find bookings on this date
                        const inProcessBookings = bookingsOnDate.filter(
                            booking => booking.status === 'in_process'
                        );
                        
                        const cancelledBookings = bookingsOnDate.filter(
                            booking => booking.status === 'cancelled by admin' || 
                                       booking.status === 'cancelled by student'
                        );

                        // Remove any previous selected day highlights
                        document.querySelectorAll('.fc-day-selected').forEach(el => {
                            el.classList.remove('fc-day-selected');
                        });

                        // Highlight the clicked day
                        const clickedCell = document.querySelector(`.fc-day[data-date="${date}"]`);
                        if (clickedCell) {
                            clickedCell.classList.add('fc-day-selected');
                        }

                        // Determine which booking to display
                        if (inProcessBookings.length > 0) {
                            // If there are in_process bookings, show the first one
                            const booking = inProcessBookings[0];
                            updateBookingDisplay(
                                { [date]: inProcessBookings }, 
                                date
                            );
                        } else if (cancelledBookings.length > 0) {
                            // If only cancelled bookings, show the first cancelled booking
                            const booking = cancelledBookings[0];
                            updateBookingDisplay(
                                { [date]: cancelledBookings }, 
                                date
                            );
                        } else {
                            // No bookings on this date
                            updateBookingDisplay({}, date);
                        }
                    },
                    events: function(fetchInfo, successCallback, failureCallback) {
                        fetch('/MedStudy-Space-System/User/php/get_user_bookings.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `start_date=${fetchInfo.startStr}&end_date=${fetchInfo.endStr}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                var events = [];
                                Object.entries(data.user_bookings).forEach(([date, bookings]) => {
                                    bookings.forEach(booking => {
                                        // Ensure only in-process and cancelled bookings for the current user are shown
                                        if (booking.status === 'in_process' || 
                                            booking.status === 'cancelled by admin' || 
                                            booking.status === 'cancelled by student') {
                                            events.push({
                                                title: '', // Empty title
                                                start: `${date}T${booking.start_time}`,
                                                end: `${date}T${booking.end_time}`,
                                                allDay: false,
                                                display: 'background', // Use background to avoid showing text
                                                extendedProps: {
                                                    bookId: booking.book_id,
                                                    roomName: booking.room_name,
                                                    bookingDate: date,
                                                    startTime: booking.start_time,
                                                    endTime: booking.end_time,
                                                    status: booking.status
                                                }
                                            });
                                        }
                                    });
                                });
                                successCallback(events);
                            } else {
                                failureCallback(new Error('Failed to fetch bookings'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            failureCallback(error);
                        });
                    }
                });

                currentCalendarInstance.render();
                return currentCalendarInstance;
            } catch (error) {
                console.error("Error initializing calendar:", error);
                return null;
            }
        }

        // New function to show cancelled booking details
        function showCancelledBookingDetails(booking) {
            const cancelledOverlay = document.getElementById('cancelledBookingDetailsOverlay');
            if (!cancelledOverlay) {
                // Create the overlay if it doesn't exist
                const newOverlay = document.createElement('div');
                newOverlay.id = 'cancelledBookingDetailsOverlay';
                newOverlay.className = 'cancelledBookingDetailsOverlay';
                newOverlay.innerHTML = `
                    <div class="cancelledBookingDetailsBox">
                        <img src="/MedStudy-Space-System/User/images/icons/warning.png" alt="">
                        <h2>Cancelled Booking Details</h2>
                        <div id="cancelledBookingInfo"></div>
                        <button onclick="closeCancelledBookingDetailsOverlay()">Close</button>
                    </div>
                `;
                document.body.appendChild(newOverlay);
            }

            // Populate booking details
            const infoContainer = document.getElementById('cancelledBookingInfo');
            infoContainer.innerHTML = `
                <p><strong>Room:</strong> ${booking.room_name}</p>
                <p><strong>Date:</strong> ${formatDate(booking.booking_date)}</p>
                <p><strong>Time:</strong> ${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}</p>
                <p><strong>Status:</strong> ${booking.status === 'cancelled by admin' ? 'Cancelled by Admin' : 'Cancelled by Student'}</p>
            `;

            // Show the overlay
            const cancelledDetailsOverlay = document.getElementById('cancelledBookingDetailsOverlay');
            cancelledDetailsOverlay.style.display = 'flex';
            setTimeout(() => {
                cancelledDetailsOverlay.classList.add('active');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        // Function to close cancelled booking details overlay
        function closeCancelledBookingDetailsOverlay() {
            const cancelledDetailsOverlay = document.getElementById('cancelledBookingDetailsOverlay');
            cancelledDetailsOverlay.classList.remove('active');
            setTimeout(() => {
                cancelledDetailsOverlay.style.display = 'none';
            }, 300);
            document.body.style.overflow = 'auto';
        }

        // Ensure calendar is initialized when DOM is loaded
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(() => {
                const calendar = initializeCalendar();
                if (!calendar) {
                    console.error("Failed to initialize calendar");
                }
            }, 100);
        });

        function navigateToScanner() {
            // Retrieve the active booking from session storage
            const activeBooking = JSON.parse(sessionStorage.getItem('activeBooking'));
            
            console.log('Active Booking for Scanner:', activeBooking);
            
            // Check if active booking exists
            if (!activeBooking) {
                showCustomAlert('No active booking available. Please book a room first.', 'error');
                return;
            }

            // Construct URL with booking details
            const baseUrl = '/MedStudy-Space-System/User/html/scanner.php';
            const scannerUrl = new URL(baseUrl, window.location.origin);

            // Add booking parameters to URL
            const params = {
                'book_id': activeBooking.book_id,
                'room_id': activeBooking.room_id,
                'booking_date': activeBooking.booking_date,
                'start_time': activeBooking.start_time,
                'end_time': activeBooking.end_time
            };

            Object.entries(params).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    scannerUrl.searchParams.set(key, value);
                }
            });

            console.log('Scanner Redirect URL:', scannerUrl.toString());

            // Redirect
            try {
                window.location.href = scannerUrl.toString();
            } catch (error) {
                console.error('Navigation Error:', error);
                showCustomAlert('Failed to navigate to scanner. Please try again.', 'error');
            }
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

        // Custom Alert Function
        function showCustomAlert(message, type = 'info') {
            // Create alert container if it doesn't exist
            let alertContainer = document.getElementById('custom-alert-container');
            if (!alertContainer) {
                alertContainer = document.createElement('div');
                alertContainer.id = 'custom-alert-container';
                alertContainer.style.position = 'fixed';
                alertContainer.style.top = '20px';
                alertContainer.style.left = '50%';
                alertContainer.style.transform = 'translateX(-50%)';
                alertContainer.style.zIndex = '1000';
                document.body.appendChild(alertContainer);
            }

            // Create alert element
            const alertElement = document.createElement('div');
            alertElement.style.backgroundColor = 
                type === 'error' ? '#ff4d4d' : 
                type === 'warning' ? '#ffa500' : 
                type === 'success' ? '#4CAF50' : '#2196F3';
            alertElement.style.color = 'white';
            alertElement.style.padding = '15px';
            alertElement.style.borderRadius = '5px';
            alertElement.style.margin = '10px';
            alertElement.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            alertElement.style.maxWidth = '300px';
            alertElement.style.textAlign = 'center';
            
            alertElement.textContent = message;

            // Add close button
            const closeBtn = document.createElement('span');
            closeBtn.innerHTML = '&times;';
            closeBtn.style.float = 'right';
            closeBtn.style.cursor = 'pointer';
            closeBtn.style.fontWeight = 'bold';
            closeBtn.onclick = () => alertElement.remove();
            alertElement.appendChild(closeBtn);

            // Add to container
            alertContainer.appendChild(alertElement);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertElement.parentNode) {
                    alertElement.remove();
                }
            }, 5000);
        }
    </script>

    <!-- Calendar Styles -->
    <style>
        /* Simplified Calendar Container */
        #calendar {
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        /* Calendar Header */
        .fc .fc-toolbar {
            margin-bottom: 1em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .fc .fc-toolbar-title {
            font-size: 1.5em;
            color: #014E6F;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .fc .fc-button-primary {
            background-color: transparent;
            border: none;
            color: #014E6F;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 8px 12px;
        }

        .fc .fc-button-primary:hover {
            background-color: rgba(1, 78, 111, 0.1);
            transform: scale(1.05);
        }

        /* Day Headers */
        .fc .fc-col-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .fc .fc-col-header-cell {
            padding: 10px 0;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8em;
        }

        /* Day Cells */
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

        /* Booked Date Styling */
        .fc .fc-day-user-booking .fc-daygrid-day-number {
            color: #006CFD !important;
            font-weight: bold;
            transform: scale(1.2);
        }

        /* Fully Booked Styling */
        .fc .fc-day-fully-booked .fc-daygrid-day-number {
            color: #E74C3C !important;
            font-weight: bold;
        }

        /* Available Days */
        .fc .fc-day-available .fc-daygrid-day-number {
            color: #52BBBF !important;
        }

        /* Past Days */
        .fc .fc-day-past .fc-daygrid-day-number {
            color: #ADB5BD !important;
            text-decoration: line-through;
        }

        /* Weekend Days */
        .fc .fc-day-sat .fc-daygrid-day-number,
        .fc .fc-day-sun .fc-daygrid-day-number {
            color: #CED4DA !important;
            cursor: not-allowed;
        }

        /* Hover Effects */
        .fc .fc-daygrid-day:hover {
            background-color: rgba(82, 187, 191, 0.02);
            transition: background-color 0.3s ease;
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

        /* Legend Styling */
        .legendForCalendar {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            gap: 20px; /* Add space between legend items */
        }

        .legend {
            display: flex;
            align-items: center;
            margin: 0;
        }

        .legend img {
            width: 24px;
            height: 24px;
            margin-right: 8px;
            border-radius: 50%; /* Ensure circular shape */
        }

        .legend span {
            font-size: 0.9em;
            color: #495057;
            font-weight: 500;
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

        /* Calendar Day Number Colors */
        .fc .fc-day-user-booking .day-number {
            color: #006CFD !important;
            font-weight: bold;
        }

        .fc .fc-day-cancelled .day-number {
            color: #FF4D4D !important;
            font-weight: bold;
        }

        /* Calendar Event Background Colors */
        .fc .fc-day-user-booking {
            background-color: rgba(0, 108, 253, 0.1) !important;
        }

        .fc .fc-day-cancelled {
            background-color: rgba(255, 77, 77, 0.1) !important;
        }

        /* Cancelled Booking Details Overlay */
        .cancelledBookingDetailsOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .cancelledBookingDetailsOverlay.active {
            display: flex;
            opacity: 1;
        }

        .cancelledBookingDetailsBox {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .cancelledBookingDetailsBox img {
            width: 60px;
            height: 60px;
            margin-bottom: 20px;
        }

        .cancelledBookingDetailsBox h2 {
            color: #FF4D4D;
            margin-bottom: 20px;
        }

        .cancelledBookingDetailsBox p {
            margin: 10px 0;
            color: #495057;
        }

        .cancelledBookingDetailsBox button {
            background-color: #FF4D4D;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .cancelledBookingDetailsBox button:hover {
            background-color: #CC0000;
        }

        /* Selected Day Styling */
        .fc .fc-day-selected .day-number {
            background-color: rgba(0, 108, 253, 0.2) !important;
            border-radius: 50%;
            padding: 5px;
        }

        .fc .fc-day-selected {
            background-color: rgba(0, 108, 253, 0.05) !important;
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
    </script>
</body>
</html>