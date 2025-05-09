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
        <div class="room">
            <div class="roomImage">
                <img src="/MedStudy-Space-System/User/images/icons/studyRoom2.jpg" alt="">
            </div>
            <div class="roomInfo">
                <h3>Study Room 1</h3>
                <span>Available</span>
                <br><br>
                <button onclick="openRoomOverlay()">Book</button>
            </div>
        </div>
    </div>

    <div class="roomOverlay" id="roomOverlay">
        <div class="roomBox">
            <img src="/MedStudy-Space-System/User/images/icons/left-arrow.png" id="backArrow" alt="" onclick="closeRoomOverlay()">
            <div class="roomBoxContainer">
                <div class="leftRoom">
                    <img class="ga" src="/MedStudy-Space-System/User/images/icons/studyRoom2.jpg" alt="">
                    <h1>Study Room 1</h1>
                    <span>Available</span>
                    <div class="roomBoxDescription">
                        <div class="leftRoomBoxDesc">
                            <div class="additionalInfo">
                                <img src="/MedStudy-Space-System/User/images/icons/crowd-of-users.png" alt="">
                                <span>5 Students</span>
                            </div>
                            <div class="additionalInfo">
                                <img src="/MedStudy-Space-System/User/images/icons/swivel-chair.png" alt="">
                                <span>5 Chairs</span>
                            </div>
                            <div class="additionalInfo">
                                <img src="/MedStudy-Space-System/User/images/icons/table.png" alt="">
                                <span>1 Table</span>
                            </div>
                        </div>
                        <div class="rightRoomBoxDesc">
                            <img src="/MedStudy-Space-System/User/images/icons/time-left.png" alt="">
                            <p>2 hours</p>
                        </div>
                    </div>
                </div>
                <div class="rightRoom">
                    <h2>Available Dates</h2>
                    <div id="calendar"></div>
                    <div class="legendForCalendarRoom">
                        <div class="legendRoom">
                            <img src="/MedStudy-Space-System/User/images/icons/legendOccupied.png" alt="">
                            <span>Vacant</span>
                        </div>
                        <div class="legendRoom">
                            <img src="/MedStudy-Space-System/User/images/icons/legendUnnoccupied.png" alt="">
                            <span>Fully Booked</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bookOverlay" id="bookOverlay">
        <div class="bookBox">
            <img src="/MedStudy-Space-System/User/images/icons/left-arrow.png" alt="" onclick="closeBookOverlay()">
            <div class="bookDescription">
                <h1>Study Room 1</h1>
                <span>Monday, March 24</span>
                <div class="setTime">
                    <h2>Select Starting Time</h2>
                    <input type="text" id="timePicker">
                    <h2>End Time</h2>
                    <input type="text" id="endTimePicker">
                    <br>
                    <button onclick="openBookConfirmaationOverlay()">Book</button>
                </div>
            </div>
        </div>
    </div>

    <div class="bookConfirmationOverlay" id="bookConfirmationOverlay">
        <div class="bookConfirmationBox">
            <p>Do you want to book Study Room 1?</p>
            <div>
                <button onclick="closeBookConfirmationOverlay()">No</button>
                <button onclick="confirmBook()">Yes</button>
            </div>
        </div>
    </div>

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
            <button onclick="closeConfirmedBook()">Back</button>
        </div>
    </div>

    <div class="fullyBookedOverlay" id="fullyBookedOverlay">
        <div class="fullyBookedBox">
            <img src="/MedStudy-Space-System/User/images/icons/information.png" alt="">
            <p>This date is fully booked.</p>
            <button onclick="closeFullyBookedOverlay()">Back</button>
        </div>
    </div>

    <div class="notAvailableOverlay" id="notAvailableOverlay">
        <div class="notAvailableBox">
            <img src="/MedStudy-Space-System/User/images/icons/warning.png" alt="">
            <p>This date is not available.</p>
            <button onclick="closeNotAvailableOverlay()">Back</button>
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

    <div class="popup" id="loadingPopup">
        <div class="popup-content">
            <p>Please wait for a moment<br><br>Saving....</p>
            <img src="/MedStudy-Space-System/User/images/logos/medstudyLogo.png" alt="Loading" class="loading-image">
        </div>
    </div>

    <!-- Script for Overlays -->
    <script>
        function openRoomOverlay() {
            const overlay = document.getElementById("roomOverlay");
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
            if (window.myCalendar) {
                window.myCalendar.render();
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

        function openBookOverlay() {
            const overlay = document.getElementById("bookOverlay");
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
            const overlay = document.getElementById("bookConfirmationOverlay");
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

        function confirmBook() {
            closeBookConfirmationOverlay();
            closeBookOverlay();
            closeRoomOverlay();
            const overlay = document.getElementById("bookConfirmedOverlay");
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
            localStorage.setItem("showBookingDetails", "true");
            localStorage.setItem("bookingConfirmed", "true");
            if (window.myCalendar) {
                window.myCalendar.render();
            }
        }

        function closeConfirmedBook() {
            const overlay = document.getElementById("bookConfirmedOverlay");
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
            }, 300);
            document.body.style.overflow = "auto";
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
                    showLoading('/MedStudy-Space-System/User/html/home.php');
                } else {
                    alert('Failed to update profile: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error updating profile: ' + error);
            });
        }
    </script>

    <!-- For time input -->
    <script>
        flatpickr("#timePicker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i K",
            time_24hr: false,
            defaultDate: "08:00 AM",
            onReady: function(selectedDates, dateStr, instance) {
                instance.input.value = "08:00 AM";
            }
        });

        flatpickr("#endTimePicker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i K",
            time_24hr: false,
            defaultDate: "10:00 AM",
            onReady: function(selectedDates, dateStr, instance) {
                instance.input.value = "10:00 AM";
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
</body>
</html>