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
    <link rel="stylesheet" href="..\css\home.css">
    <link rel="icon" href="..\images\logos\medstudyLogo.png">
    <link rel="stylesheet" href="..\css\scanner.css">
    <link rel="stylesheet" href="..\css\schedule.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="..\js\cameraFunction.js"></script>
    <script src="..\js\linkActive.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/dist/html5-qrcode.min.js"></script>
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
                <video id="camera" autoplay playsinline></video>
                <div class="qr-guide-box"></div>
                <canvas id="qr-overlay" class="qr-overlay"></canvas>
                <div class="qr-scanning-status scanning">Scanning QR Code...</div>
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

    <script>
        // Log immediately to confirm script execution start
        console.log('Scanner script started.');
        
        // Global error handler to catch uncaught JavaScript errors
        window.onerror = function(message, source, lineno, colno, error) {
            logToServer('JavaScript Error: ' + message + ' at ' + source + ':' + lineno + ':' + colno + ' Error Object: ' + (error ? error.stack : 'N/A'));
            updateScanningStatus('error', 'Script Error: Check Console');
            showErrorPrompt('A script error occurred. Please check the browser console (F12) for details or refresh the page.');
            return false;
        };
        
        // Function to log messages to server-side debug log
        function logToServer(message) {
            console.log(message); // Also log to browser console for immediate feedback
            fetch('log_to_debug.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: message })
            }).catch(error => {
                console.error('Error logging to server:', error);
            });
        }

        // Log that the script has started
        logToServer('Scanner script execution started.');

        // Check if jsQR library is loaded
        if (typeof jsQR !== 'undefined') {
            logToServer('jsQR library loaded successfully.');
        } else {
            logToServer('jsQR library NOT loaded. QR scanning will not work.');
        }

        let currentBooking = <?php echo json_encode($booking ?? null); ?>;
        let videoStream = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('camera');
            const scannerContainer = document.querySelector('.scannerContainer');
            const rightScanner = document.querySelector('.rightScanner');
            const scanningStatus = document.querySelector('.qr-scanning-status');
            const qrOverlay = document.getElementById('qr-overlay');
            const qrGuideBox = document.querySelector('.qr-guide-box');
            let ctx = qrOverlay.getContext('2d');

            // Log that the DOM is fully loaded and script execution has started
            logToServer('DOM fully loaded. Starting scanner initialization.');

            // Additional log to check if video element is found
            if (video) {
                logToServer('Video element found in DOM.');
            } else {
                logToServer('ERROR: Video element NOT found in DOM. Camera initialization will fail.');
            }

            // Ensure scanning status is visible
            scanningStatus.style.display = 'block';
            scanningStatus.style.position = 'absolute';
            scanningStatus.style.bottom = '10px';
            scanningStatus.style.left = '50%';
            scanningStatus.style.transform = 'translateX(-50%)';
            scanningStatus.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
            scanningStatus.style.color = 'white';
            scanningStatus.style.padding = '10px 20px';
            scanningStatus.style.borderRadius = '10px';
            scanningStatus.style.fontSize = '16px';
            scanningStatus.style.zIndex = '10';
            scanningStatus.style.textAlign = 'center';
            scanningStatus.style.minWidth = '200px';

            // Adjust guide box size based on video dimensions once loaded
            function updateGuideBox() {
                const videoWidth = video.clientWidth;
                const videoHeight = video.clientHeight;
                const boxSize = Math.min(videoWidth, videoHeight) * 0.6; // 60% of smallest dimension

                qrGuideBox.style.width = `${boxSize}px`;
                qrGuideBox.style.height = `${boxSize}px`;
                qrGuideBox.style.position = 'absolute';
                qrGuideBox.style.top = '50%';
                qrGuideBox.style.left = '50%';
                qrGuideBox.style.transform = 'translate(-50%, -50%)';
                qrGuideBox.style.border = '2px dashed #00ff00';
                qrGuideBox.style.backgroundColor = 'rgba(0, 255, 0, 0.1)';
                qrGuideBox.style.zIndex = '5';
                qrGuideBox.style.pointerEvents = 'none';
                qrGuideBox.style.display = 'block';

                // Update overlay dimensions to match video
                qrOverlay.width = videoWidth;
                qrOverlay.height = videoHeight;
                logToServer(`Guide box updated. Video dimensions: ${videoWidth}x${videoHeight}, Box size: ${boxSize}x${boxSize}`);
            }

            // Prevent multiple error prompts
            let errorShown = false;
            let warningShown = false;

            // Function to update scanning status
            function updateScanningStatus(status, message) {
                scanningStatus.className = `qr-scanning-status ${status}`;
                scanningStatus.textContent = message;
                scanningStatus.style.display = 'block'; // Ensure it's always visible when updated
                // Adjust colors based on status for better visibility
                if (status === 'success') {
                    scanningStatus.style.backgroundColor = 'rgba(0, 128, 0, 0.7)';
                } else if (status === 'error' || status === 'warning') {
                    scanningStatus.style.backgroundColor = 'rgba(255, 0, 0, 0.7)';
                } else if (status === 'detecting') {
                    scanningStatus.style.backgroundColor = 'rgba(255, 165, 0, 0.7)';
                } else {
                    scanningStatus.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
                }
                logToServer(`Scanning status updated: ${status} - ${message}`);
            }

            // Function to draw bounding box
            function drawBoundingBox(location) {
                ctx.clearRect(0, 0, qrOverlay.width, qrOverlay.height);
                qrOverlay.style.display = 'block';

                const topLeft = location.topLeftCorner;
                const topRight = location.topRightCorner;
                const bottomLeft = location.bottomLeftCorner;
                const bottomRight = location.bottomRightCorner;

                ctx.strokeStyle = '#00ff00';
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.moveTo(topLeft.x, topLeft.y);
                ctx.lineTo(topRight.x, topRight.y);
                ctx.lineTo(bottomRight.x, bottomRight.y);
                ctx.lineTo(bottomLeft.x, bottomLeft.y);
                ctx.closePath();
                ctx.stroke();

                // Fade out after a moment
                setTimeout(() => {
                    ctx.clearRect(0, 0, qrOverlay.width, qrOverlay.height);
                    qrOverlay.style.display = 'none';
                }, 1000);
                logToServer(`Bounding box drawn for detected QR code.`);
            }

            // Function to update booking status via AJAX
            function updateBookingStatus(bookId, status) {
                logToServer('Updating booking status for book_id ' + bookId + ' to ' + status);
                return fetch('update_booking_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        book_id: bookId,
                        status: status
                    })
                });
            }

            // Function to validate booking time
            function validateBookingTime(booking) {
                const now = new Date();
                const bookingDate = new Date(booking.booking_date + ' ' + booking.start_time);
                const bookingEndTime = new Date(booking.booking_date + ' ' + booking.end_time);
                
                // Check booking time status
                const minutesUntilBooking = (bookingDate - now) / (1000 * 60);
                const minutesAfterBooking = (now - bookingEndTime) / (1000 * 60);

                if (minutesUntilBooking > 0) {
                    logToServer(`Booking not started yet. Starts in ${Math.round(minutesUntilBooking)} minutes.`);
                    return {
                        status: 'waiting',
                        message: `Your booking starts in ${Math.round(minutesUntilBooking)} minutes. Please wait until your booking time to use the room.`
                    };
                }

                if (minutesAfterBooking > 0) {
                    logToServer(`Booking expired. Ended ${Math.round(minutesAfterBooking)} minutes ago.`);
                    return {
                        status: 'late',
                        message: 'Booking time has expired.'
                    };
                }

                logToServer(`Booking is active and valid for scanning.`);
                return {
                    status: 'valid',
                    message: 'Booking is active.'
                };
            }

            // QR Code Scanning Function using jsQR
            function scanQRCode(video) {
                if (!currentBooking) {
                    updateScanningStatus('error', 'No Active Booking');
                    showErrorPrompt('No active booking found.');
                    logToServer('No active booking found. Scanning aborted.');
                    showManualInputFallback();
                    return;
                }

                if (typeof jsQR === 'undefined') {
                    updateScanningStatus('error', 'Scanning Library Not Loaded');
                    showErrorPrompt('QR scanning library failed to load. Please refresh the page.');
                    logToServer('jsQR library not loaded during scan attempt.');
                    showManualInputFallback();
                    return;
                }

                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.style.display = 'none';
                document.body.appendChild(canvas);

                console.log('Starting QR code scanning with jsQR for room key: ', currentBooking.room_key);
                updateScanningStatus('scanning', 'Scanning QR Code...');
                logToServer(`Starting QR code scanning with jsQR for room key: ${currentBooking.room_key}`);

                function tick() {
                    if (video.readyState === video.HAVE_ENOUGH_DATA) {
                        canvas.height = video.videoHeight;
                        canvas.width = video.videoWidth;
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height, {
                            inversionAttempts: 'dontInvert',
                        });

                        if (code) {
                            updateScanningStatus('detecting', 'QR Code Detected');
                            console.log('QR Code Detected: ', code.data);
                            drawBoundingBox(code.location); // Draw bounding box on detection
                            logToServer(`QR Code Detected: ${code.data}`);

                            // Validate QR code matches room key
                            if (code.data !== currentBooking.room_key) {
                                updateScanningStatus('error', 'Invalid Room QR Code');
                                showErrorPrompt('Invalid room. Please scan the correct room\'s QR code.');
                                console.log('QR Code mismatch. Expected: ', currentBooking.room_key, ' Got: ', code.data);
                                logToServer(`QR Code mismatch. Expected: ${currentBooking.room_key}, Got: ${code.data}`);
                                setTimeout(() => {
                                    updateScanningStatus('scanning', 'Scanning QR Code...');
                                }, 2000);
                            } else {
                                updateScanningStatus('success', 'QR Code Verified');
                                console.log('QR Code verified successfully for room key: ', currentBooking.room_key);
                                logToServer(`QR Code verified successfully for room key: ${currentBooking.room_key}`);
                                // Validate booking time
                                const timeValidation = validateBookingTime(currentBooking);

                                switch (timeValidation.status) {
                                    case 'waiting':
                                        updateScanningStatus('warning', 'Booking Not Started');
                                        showWarningPrompt(timeValidation.message);
                                        setTimeout(() => {
                                            updateScanningStatus('scanning', 'Scanning QR Code...');
                                        }, 3000);
                                        break;
                                    case 'late':
                                        updateScanningStatus('error', 'Booking Expired');
                                        showErrorPrompt(timeValidation.message);
                                        setTimeout(() => {
                                            updateScanningStatus('scanning', 'Scanning QR Code...');
                                        }, 2000);
                                        break;
                                    default:
                                        // Update booking status
                                        updateBookingStatus(currentBooking.book_id, 'completed')
                                            .then(response => response.json())
                                            .then(result => {
                                                if (result.success) {
                                                    updateScanningStatus('success', 'Booking Confirmed');
                                                    showSuccessPrompt('Room access granted! Enjoy your study session.');
                                                    logToServer(`Booking status updated to completed for book_id: ${currentBooking.book_id}`);
                                                    setTimeout(() => {
                                                        window.location.href = 'schedule.php';
                                                    }, 2500);
                                                } else {
                                                    updateScanningStatus('error', 'Update Failed');
                                                    showErrorPrompt(result.error || 'Failed to update booking status.');
                                                    logToServer(`Failed to update booking status: ${result.error || 'Unknown error'}`);
                                                    setTimeout(() => {
                                                        updateScanningStatus('scanning', 'Scanning QR Code...');
                                                    }, 2000);
                                                }
                                            })
                                            .catch(error => {
                                                updateScanningStatus('error', 'Server Error');
                                                showErrorPrompt('Error updating booking status: ' + error.message);
                                                logToServer(`Server error updating booking status: ${error.message}`);
                                                setTimeout(() => {
                                                    updateScanningStatus('scanning', 'Scanning QR Code...');
                                                }, 2000);
                                            });
                                        break;
                                }
                            }
                        } else {
                            updateScanningStatus('scanning', 'Scanning QR Code...');
                        }
                    }
                    requestAnimationFrame(tick);
                }

                requestAnimationFrame(tick);
            }

            // Prompt Functions
            function showErrorPrompt(message) {
                if (errorShown) return;
                errorShown = true;

                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-prompt animate__animated animate__shakeX';
                errorDiv.innerHTML = `
                    <img src="..\images\icons\warningNotif.png" alt="Error">
                    <p>${message}</p>
                    <button onclick="this.parentElement.remove(); errorShown = false;">Dismiss</button>
                `;
                scannerContainer.appendChild(errorDiv);
                logToServer(`Error prompt shown: ${message}`);
            }

            function showWarningPrompt(message) {
                if (warningShown) return;
                warningShown = true;

                const warningDiv = document.createElement('div');
                warningDiv.className = 'warning-prompt animate__animated animate__fadeIn';
                warningDiv.innerHTML = `
                    <img src="..\images\icons\warning.png" alt="Warning">
                    <p>${message}</p>
                    <button onclick="this.parentElement.remove(); warningShown = false;">Dismiss</button>
                `;
                scannerContainer.appendChild(warningDiv);
                logToServer(`Warning prompt shown: ${message}`);
            }

            function showSuccessPrompt(message) {
                const successDiv = document.createElement('div');
                successDiv.className = 'success-prompt animate__animated animate__fadeIn';
                successDiv.innerHTML = `
                    <img src="..\images\icons\success-icon.png" alt="Success">
                    <p>${message}</p>
                `;
                scannerContainer.appendChild(successDiv);
                logToServer(`Success prompt shown: ${message}`);
            }

            // Fallback for manual input if camera fails
            function showManualInputFallback() {
                const manualInputDiv = document.createElement('div');
                manualInputDiv.className = 'manual-input-prompt';
                manualInputDiv.innerHTML = `
                    <h3>Camera Not Working?</h3>
                    <p>Enter the room key manually if the camera or QR scanner is not working.</p>
                    <input type="text" id="manualRoomKey" placeholder="Enter Room Key">
                    <button onclick="validateManualInput()">Submit</button>
                `;
                rightScanner.appendChild(manualInputDiv);
                logToServer('Manual input fallback shown due to camera or library failure.');
            }

            // Validate manually entered room key
            function validateManualInput() {
                const manualKey = document.getElementById('manualRoomKey').value.trim();
                logToServer(`Manual input received: ${manualKey}`);
                if (!currentBooking) {
                    updateScanningStatus('error', 'No Active Booking');
                    showErrorPrompt('No active booking found for manual validation.');
                    logToServer('No active booking for manual input validation.');
                    return;
                }

                if (manualKey === currentBooking.room_key) {
                    updateScanningStatus('success', 'Room Key Verified Manually');
                    logToServer(`Manual input verified successfully for room key: ${currentBooking.room_key}`);
                    const timeValidation = validateBookingTime(currentBooking);

                    switch (timeValidation.status) {
                        case 'waiting':
                            updateScanningStatus('warning', 'Booking Not Started');
                            showWarningPrompt(timeValidation.message);
                            break;
                        case 'late':
                            updateScanningStatus('error', 'Booking Expired');
                            showErrorPrompt(timeValidation.message);
                            break;
                        default:
                            updateBookingStatus(currentBooking.book_id, 'completed')
                                .then(response => response.json())
                                .then(result => {
                                    if (result.success) {
                                        updateScanningStatus('success', 'Booking Confirmed');
                                        showSuccessPrompt('Room access granted! Enjoy your study session.');
                                        logToServer(`Booking status updated to completed for book_id: ${currentBooking.book_id}`);
                                        setTimeout(() => {
                                            window.location.href = 'schedule.php';
                                        }, 2500);
                                    } else {
                                        updateScanningStatus('error', 'Update Failed');
                                        showErrorPrompt(result.error || 'Failed to update booking status.');
                                        logToServer(`Failed to update booking status: ${result.error || 'Unknown error'}`);
                                    }
                                })
                                .catch(error => {
                                    updateScanningStatus('error', 'Server Error');
                                    showErrorPrompt('Error updating booking status: ' + error.message);
                                    logToServer(`Server error updating booking status: ${error.message}`);
                                });
                            break;
                    }
                } else {
                    updateScanningStatus('error', 'Invalid Room Key');
                    showErrorPrompt('Invalid room key entered. Please try again.');
                    logToServer(`Manual input mismatch. Expected: ${currentBooking.room_key}, Got: ${manualKey}`);
                }
            }

            // Camera Access Function with simplified approach
            async function startAutoScanning() {
                logToServer('Starting camera access attempt with simplified approach.');
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    updateScanningStatus('error', 'Camera Not Supported');
                    showErrorPrompt('Camera access not supported by this browser.');
                    logToServer('Camera access not supported by this browser.');
                    showManualInputFallback();
                    return;
                }

                logToServer('Browser supports camera access. Requesting permission.');
                try {
                    videoStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'environment' // Prefer rear camera on mobile devices
                        }
                    });

                    video.srcObject = videoStream;
                    video.setAttribute('playsinline', true); // Important for iOS Safari
                    video.play();
                    updateScanningStatus('scanning', 'Camera Initialized, Scanning QR Code...');
                    logToServer('Camera initialized successfully. Starting scanning with jsQR.');

                    video.onloadedmetadata = () => {
                        console.log('Camera loaded, video dimensions: ', video.videoWidth, 'x', video.videoHeight);
                        updateGuideBox(); // Set guide box size once video dimensions are available
                        logToServer(`Camera loaded. Video dimensions: ${video.videoWidth}x${video.videoHeight}`);
                        // Start QR code scanning
                        scanQRCode(video);
                    };
                } catch (error) {
                    logToServer(`Camera access failed: ${error.name} - ${error.message}`);
                    if (error.name === 'NotAllowedError') {
                        logToServer('Camera access denied by user.');
                        showErrorPrompt('Camera access denied. Please allow camera access in browser settings.');
                    } else if (error.name === 'NotFoundError') {
                        logToServer('No camera found on device.');
                        showErrorPrompt('No camera found on this device.');
                    } else if (error.name === 'NotReadableError') {
                        logToServer('Camera is in use by another application.');
                        showErrorPrompt('Camera is currently in use by another application.');
                    } else {
                        logToServer('Other camera error: ' + error.name);
                        showErrorPrompt('Error accessing camera: ' + error.message);
                    }
                    updateScanningStatus('error', 'Camera Access Failed');
                    showManualInputFallback();
                }
            }

            // Initialize scanning
            logToServer('Checking for current booking before initialization.');
            if (currentBooking) {
                console.log('Current booking loaded with room key: ', currentBooking.room_key);
                logToServer(`Current booking loaded with room key: ${currentBooking.room_key}`);
                startAutoScanning();
            } else {
                updateScanningStatus('error', 'No Active Booking');
                showErrorPrompt('No active booking found. Please book a room first.');
                logToServer('No active booking found during initialization.');
                showManualInputFallback();
            }
        });
    </script>

    <style>
        /* Responsive design for smaller devices */
        /* Existing code remains unchanged */

        /* Style for manual input fallback */
        .manual-input-prompt {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            z-index: 10;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .manual-input-prompt h3 {
            margin-top: 0;
            color: #333;
        }
        .manual-input-prompt input {
            padding: 10px;
            margin: 10px 0;
            width: 80%;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .manual-input-prompt button {
            background-color: #52BBBF;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .manual-input-prompt button:hover {
            background-color: #44999d;
        }
    </style>
</body>
</html>