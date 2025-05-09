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
                <div class="scanRoom" onclick="location.href='/MedStudy-Space-System/User/html/scanner.php'">
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
                        <img src="/MedStudy-Space-System/User/images/icons/image-.png" alt="">
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
                        <input type="text" id="middleName" placeholder="Middle Name">
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
            <p>Do you want to cancel your schedule?</p>
            <div>
                <button onclick="closeBookCancelConfirmationOverlay()">No</button>
                <button onclick="cancelBooking()">Yes</button>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.10/index.global.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var calendarEl = document.getElementById("calendar");
            if (!calendarEl) {
                console.error("Error: Calendar element not found.");
                return;
            }

            window.yourSchedule = {
                "2025-03-24": { backgroundColor: "#006CFD", color: "white", borderRadius: "100px" }
            };
            var occupiedDates = {
                "2025-03-21": { backgroundColor: "#D4E9FF", color: "#006CFD", borderRadius: "100px" },
                "2025-03-20": { backgroundColor: "#D4E9FF", color: "#006CFD", borderRadius: "100px" },
                "2025-03-19": { backgroundColor: "#D4E9FF", color: "#006CFD", borderRadius: "100px" }
            };
            var bookingConfirmed = localStorage.getItem("bookingConfirmed") === "true";

            window.myCalendar = new FullCalendar.Calendar(calendarEl, {
                initialView: "dayGridMonth",
                height: "auto",
                contentHeight: "auto",
                expandRows: true,
                headerToolbar: {
                    left: "prev,next today",
                    center: "title",
                    right: ""
                },
                dayCellDidMount: function (info) {
                    let dateStr = info.date.toLocaleDateString("en-CA");
                    if (bookingConfirmed && window.yourSchedule[dateStr]) {
                        let style = window.yourSchedule[dateStr];
                        info.el.style.backgroundColor = style.backgroundColor;
                        info.el.style.color = style.color;
                        info.el.style.borderRadius = style.borderRadius;
                        info.el.style.cursor = "pointer";
                    } else if (occupiedDates[dateStr]) {
                        let style = occupiedDates[dateStr];
                        info.el.style.backgroundColor = style.backgroundColor;
                        info.el.style.color = style.color;
                        info.el.style.borderRadius = style.borderRadius;
                        info.el.style.cursor = "pointer";
                    } else {
                        info.el.style.cursor = "pointer";
                    }
                },
                dateClick: function (info) {
                    let dateStr = info.date.toLocaleDateString("en-CA");
                    if (window.yourSchedule[dateStr]) {
                        openBookOverlay();
                    } else if (occupiedDates[dateStr]) {
                        openFullyBookedOverlay();
                    } else {
                        openNotAvailableOverlay();
                    }
                }
            });
            window.myCalendar.render();
        });

        function cancelBookingDate() {
            localStorage.removeItem("bookingConfirmed");
            window.yourSchedule = {};
            if (window.myCalendar) {
                window.myCalendar.render();
            }
            location.reload();
        }
    </script>

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
                    showLoading('/MedStudy-Space-System/User/html/schedule.php');
                } else {
                    alert('Failed to update profile: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error updating profile: ' + error);
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
            const overlay = document.getElementById("bookCancelConfirmationOverlay");
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
        }
        function closeBookCancelConfirmationOverlay() {
            const overlay = document.getElementById("bookCancelConfirmationOverlay");
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

        document.addEventListener("DOMContentLoaded", function () {
            const shouldShowBookingDetails = localStorage.getItem("showBookingDetails");
            if (shouldShowBookingDetails === "true") {
                const bookingDetails = document.getElementById("bookingDetails");
                const bookingInformation = document.getElementById("bookingInformation");
                const noBooking = document.getElementById("noBooking");
                if (bookingDetails) bookingDetails.style.display = "block";
                if (bookingInformation) bookingInformation.style.display = "block";
                if (noBooking) noBooking.style.display = "none";
            }
        });

        function cancelBooking() {
            const bookingDetails = document.getElementById("bookingDetails");
            const bookingInformation = document.getElementById("bookingInformation");
            const noBooking = document.getElementById("noBooking");
            if (bookingDetails) bookingDetails.style.display = "none";
            if (bookingInformation) bookingInformation.style.display = "none";
            if (noBooking) noBooking.style.display = "block";
            localStorage.removeItem("showBookingDetails");
            cancelBookingDate();
            closeBookCancelConfirmationOverlay();
        }
    </script>
</body>
</html>