<?php
session_start();

// Enable detailed error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Custom debug logging function to write to a file
function writeDebugLog($message) {
    $logFile = dirname(__FILE__) . '/../logs/debug_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] $message\n";
    
    // Ensure the logs directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Write to file
    $result = file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    if ($result === false) {
        error_log("Failed to write to debug_log.txt at $logFile");
    }
}

// Log request details
writeDebugLog("Scanner Page Accessed");
writeDebugLog("GET Parameters: " . json_encode($_GET));

// Redirect to login if not authenticated
if (!isset($_SESSION['email'])) {
    writeDebugLog("User not authenticated, redirecting to login");
    header("Location: /MedStudy-Space-System/index.php");
    exit();
}

// Log all GET parameters for debugging
error_log("Scanner Page - GET Parameters: " . json_encode($_GET));

// Database connection with enhanced error handling
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Database Connection Failed: " . $conn->connect_error);
    }

    // Fetch user details
    $email = $_SESSION['email'];
    $user_stmt = $conn->prepare("SELECT user_id FROM user_accounts WHERE email = ?");
    $user_stmt->bind_param("s", $email);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("User not found for email: $email");
    }
    
    $user_id = $user['user_id'];
    $user_stmt->close();

    // Log user details for debugging
    writeDebugLog("User ID for Booking Retrieval: $user_id");
    writeDebugLog("Current Date: " . date('Y-m-d'));
    writeDebugLog("Current Time: " . date('H:i:s'));

    // Log detailed user and session information
    writeDebugLog("Scanner Page Debug Information:");
    writeDebugLog("Session Email: " . ($_SESSION['email'] ?? 'NOT SET'));
    writeDebugLog("User ID: " . ($user_id ?? 'NOT FOUND'));
    writeDebugLog("Current Timestamp: " . date('Y-m-d H:i:s'));

    // Check for passed booking parameters
    $passed_book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : null;
    $passed_room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : null;
    $passed_booking_date = isset($_GET['booking_date']) ? $_GET['booking_date'] : null;
    $passed_start_time = isset($_GET['start_time']) ? $_GET['start_time'] : null;
    $passed_end_time = isset($_GET['end_time']) ? $_GET['end_time'] : null;

    // Detailed logging of passed parameters
    writeDebugLog("Passed Booking Parameters:");
    writeDebugLog("Book ID: " . ($passed_book_id ?? 'NULL'));
    writeDebugLog("Room ID: " . ($passed_room_id ?? 'NULL'));
    writeDebugLog("Booking Date: " . ($passed_booking_date ?? 'NULL'));
    writeDebugLog("Start Time: " . ($passed_start_time ?? 'NULL'));
    writeDebugLog("End Time: " . ($passed_end_time ?? 'NULL'));

    // Prepare a comprehensive query to fetch booking details
    $stmt = $conn->prepare("SELECT b.book_id, b.room_id, b.booking_date, b.start_time, b.end_time, b.status, r.room_name, r.room_key, r.qr_code, r.student_capacity, r.room_image FROM booking b JOIN rooms r ON b.room_id = r.room_id WHERE b.user_id = ? AND b.status NOT IN ('cancelled', 'completed', 'no_show') AND ((? IS NOT NULL AND b.book_id = ?) OR (? IS NOT NULL AND b.room_id = ? AND b.booking_date = ?)) ORDER BY b.booking_date, b.start_time LIMIT 1");

    if ($stmt) {
        $stmt->bind_param(
            "iiiiis", 
            $user_id, 
            $passed_book_id, $passed_book_id,
            $passed_room_id, $passed_room_id, $passed_booking_date
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        $stmt->close();

        // Log the retrieved booking
        if ($booking) {
            writeDebugLog("Booking Found: " . json_encode($booking));
            writeDebugLog("Room Key for Verification: " . ($booking['room_key'] ?? 'NOT SET'));
        } else {
            writeDebugLog("No matching booking found with provided parameters");
            writeDebugLog("Query Parameters Used: user_id=$user_id, book_id=" . ($passed_book_id ?? 'NULL') . ", room_id=" . ($passed_room_id ?? 'NULL') . ", booking_date=" . ($passed_booking_date ?? 'NULL'));
            // Log all bookings for this user to see if the booking exists
            $debug_stmt = $conn->prepare("SELECT book_id, room_id, booking_date, start_time, end_time, status FROM booking WHERE user_id = ? ORDER BY booking_date, start_time");
            $debug_stmt->bind_param("i", $user_id);
            $debug_stmt->execute();
            $debug_result = $debug_stmt->get_result();
            $all_bookings = [];
            while ($row = $debug_result->fetch_assoc()) {
                $all_bookings[] = $row;
            }
            writeDebugLog("All Bookings for User $user_id: " . json_encode($all_bookings));
            $debug_stmt->close();
            // Additional debug: Check if the specific book_id exists for any user
            if ($passed_book_id) {
                $book_id_debug_stmt = $conn->prepare("SELECT book_id, user_id, room_id, booking_date, start_time, end_time, status FROM booking WHERE book_id = ?");
                $book_id_debug_stmt->bind_param("i", $passed_book_id);
                $book_id_debug_stmt->execute();
                $book_id_result = $book_id_debug_stmt->get_result();
                $book_id_data = $book_id_result->fetch_assoc();
                writeDebugLog("Booking ID $passed_book_id Data (if exists): " . json_encode($book_id_data ?? 'Not found'));
                $book_id_debug_stmt->close();
            }
        }
    } else {
        writeDebugLog("Failed to prepare booking retrieval statement: " . $conn->error);
    }
} catch (Exception $e) {
    writeDebugLog("Booking Retrieval Error: " . $e->getMessage());
    $booking = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedStudy | Scanner</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/dist/html5-qrcode.min.js" 
            onerror="loadAlternativeQRLibrary()"
            crossorigin="anonymous"></script>
    <link rel="stylesheet" href="..\css\home.css">
    <link rel="icon" href="..\images\logos\medstudyLogo.png">
    <link rel="stylesheet" href="..\css\scanner.css">
    <link rel="stylesheet" href="..\css\schedule.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="..\js\cameraFunction.js"></script>
    <script src="..\js\linkActive.js"></script>
</head>
<body>
    <div class="navBar">
        <div class="leftNav">
            <div class="logo" onclick="location.href='home.php'">
            <h2>
                <img src="..\images\logos\medstudyLogoResized.png" alt="">
                <span>
                <span style="color: #52BBBF;">Med</span>Study
                </span>
            </h2>
            </div>

            <div class="navLinks">
                <a href="home.php" class="link">Home</a>
                <a href="schedule.php" class="link active">Schedule</a>
            </div>
        </div>
        <div class="rightNav">
            <button onclick="location.href='/MedStudy-Space-System/index.php'">Log out</button>
        </div>
    </div>

    <div align="right" class="logout-mobile">
        <img src="..\images\icons\logout3.png" alt="" onclick="location.href='/MedStudy-Space-System/index.php'">
        <span>Log out</span>
    </div>

    <div class="scanner">
        <div class="backButton" onclick="location.href='schedule.php'">
            <img src="..\images\icons\left-arrow.png" alt="">
        </div>

        <div class="scannerContainer">
            <div class="leftScanner">
                <img src="<?php echo htmlspecialchars($booking && isset($booking['room_image']) && !empty($booking['room_image']) ? 'data:image/jpeg;base64,' . base64_encode($booking['room_image']) : '..\\images\\icons\\studyRoom2.jpg'); ?>" alt="Room Image">
                <h1><?php echo htmlspecialchars($booking ? $booking['room_name'] : 'Study Room 1'); ?></h1>
                
                <?php if ($booking): ?>
                <div class="booking-details-container">
                    <div class="detail-row">
                        <span class="label">Booking Status</span>
                        <span class="value">
                            <?php 
                            $statusClass = '';
                            switch($booking['status']) {
                                case 'in_process':
                                    $statusClass = 'in-process';
                                    echo '<span class="status-badge in-process">Waiting</span>';
                                    break;
                                case 'completed':
                                    $statusClass = 'completed';
                                    echo '<span class="status-badge completed">Completed</span>';
                                    break;
                                case 'cancelled':
                                    $statusClass = 'cancelled';
                                    echo '<span class="status-badge cancelled">Cancelled</span>';
                                    break;
                                case 'no_show':
                                    $statusClass = 'no-show';
                                    echo '<span class="status-badge no-show">No Show</span>';
                                    break;
                                default:
                                    echo '<span class="status-badge">Unknown</span>';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Date</span>
                        <span class="value"><?php 
                            $bookingDate = new DateTime($booking['booking_date']);
                            echo htmlspecialchars($bookingDate->format('M d, Y')); 
                        ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Start Time</span>
                        <span class="value"><?php 
                            $startTime = new DateTime($booking['start_time']);
                            echo htmlspecialchars($startTime->format('h:i A')); 
                        ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">End Time</span>
                        <span class="value"><?php 
                            $endTime = new DateTime($booking['end_time']);
                            echo htmlspecialchars($endTime->format('h:i A')); 
                        ?></span>
                    </div>
                </div>
                <?php else: ?>
                <div class="noBooking">
                    <p>No active booking found.</p>
                    <button onclick="location.href='schedule.php'">Book a Room</button>
                </div>
                <?php endif; ?>

                <!-- Room Details Section -->
                <div class="roomDescription-container">
                    <?php if ($booking): ?>
                    <?php else: ?>
                        <div class="roomDescription">
                            <img src="..\images\icons\info-circle.png" alt="">
                            <span>No room details available</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="rightScanner">
                <video id="camera" autoplay playsinline style="width:100%; height:100%; object-fit:cover;"></video>
                <div id="qr-scanner-container" style="position:absolute; top:0; left:0; width:100%; height:100%;"></div>
                <div class="qr-scanning-status scanning">Scanning QR Code...</div>
                <div class="qr-scanning-details">
                    <div class="scanning-detail-item camera-status">Camera: Initializing...</div>
                    <div class="scanning-detail-item library-status">Library: Checking...</div>
                    <div class="scanning-detail-item detection-status">Detection: Waiting...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="topFooter">
            <div class="medStudyLogo">
                <img src="..\images\logos\whiteVer2.png" alt="">
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
                    <a href="..\html\home.php">Home</a> <br>
                    <a href="..\html\schedule.php">Schedule</a>
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
                    <img src="..\images\icons\facebook-app-symbol.png" alt="">
                </div>
                <div class="icon">
                    <img src="..\images\icons\twitter.png" alt="">
                </div>
                <div class="icon">
                    <img src="..\images\icons\instagram.png" alt="">
                </div>
                <div class="icon">
                    <img src="..\images\icons\youtube.png" alt="">
                </div>
            </div>
            <br>
            <p>© 2025 University of Southeastern Philippines. All Rights Reserved.</p>
        </div>
    </div>

    <!-- Local QR Code Library Loading -->
    <script>
        // Robust library loading function
        (function() {
            function loadLocalQRCodeLibrary() {
                var script = document.createElement('script');
                script.src = '../js/html5-qrcode-master/minified/html5-qrcode.min.js';
                script.async = false;
                script.onload = function() {
                    console.log('QR Code library loaded successfully');
                    // Trigger QR scanner initialization if function exists
                    if (typeof window.initializeQRScanner === 'function') {
                        window.initializeQRScanner();
                    }
                };
                script.onerror = function() {
                    console.error('Failed to load local QR Code library');
                    
                    // Update status elements if they exist
                    var libraryStatus = document.querySelector('.scanning-detail-item.library-status');
                    var cameraStatus = document.querySelector('.scanning-detail-item.camera-status');
                    var detectionStatus = document.querySelector('.scanning-detail-item.detection-status');
                    
                    if (libraryStatus) libraryStatus.textContent = 'Library: Load Failed';
                    if (cameraStatus) cameraStatus.textContent = 'Camera: Unavailable';
                    if (detectionStatus) detectionStatus.textContent = 'Detection: Library Error';
                };
                document.head.appendChild(script);
            }

            // Load library when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', loadLocalQRCodeLibrary);
            } else {
                loadLocalQRCodeLibrary();
            }
        })();
    </script>
    <script src="..\js\cameraFunction.js"></script>
    <script src="..\js\linkActive.js"></script>
    <script src="../js/qr_codeScanning.js"></script>
    <style>
        /* Responsive design for smaller devices */
        /* Existing code remains unchanged */

        /* Additional styles for scanning details */
        .qr-scanning-details {
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
            border-radius: 10px;
            text-align: left;
            z-index: 100;
            min-width: 250px;
        }
        .scanning-detail-item {
            margin: 5px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        .scanning-detail-item::before {
            content: '•';
            margin-right: 10px;
            color: gray;
        }
        .scanning-detail-item.success::before {
            color: green;
        }
        .scanning-detail-item.error::before {
            color: red;
        }
        .scanning-detail-item.warning::before {
            color: orange;
        }
    </style>
</body>
</html>