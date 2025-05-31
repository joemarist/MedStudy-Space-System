<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Admin</title>
   
    <link rel="icon" href="../../User/images\logos\medstudyLogo.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    
    <!-- Debugging script -->
    <script>
        // Early logging and error tracking
        window.addEventListener('error', function(event) {
            console.error('Uncaught error:', event.error);
        });

        console.log('Dashboard page started loading');
    </script>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="../images/logos/adminIcon.png" alt="Admin Icon" width="100%">
            <hr>
            <ul>
                <li class="pok" data-nav="1">
                    <img src="../../User/images/icons/dashboard.png" alt="Dashboard" width="20px">
                    <span>Dashboard</span>
                </li>
                <li data-nav="2">
                    <img src="../../User/images/icons/room.png" alt="Room" width="20px">
                    <span>Room</span>
                </li>
                <li data-nav="3">
                    <img src="../../User/images/icons/appointment.png" alt="Booking" width="20px">
                    <span>Booking</span>
                </li>
                <li data-nav="4">
                    <img src="../../User/images/icons/bar-chart.png" alt="Reports" width="20px">
                    <span>Reports</span>
                </li>
            </ul>
            <hr>
            <img src="../images/logos/usep.png" alt="USEP Logo" width="100%">
        </aside>
    </div>

    <div class="box2">
        <div class="main-container">
            <header class="header">
                <h2 id="titlename">Dashboard</h2>
                <div class="header-right">
                    <div class="user-avatar">
                        <img src="../../User/images/icons/profile.png" alt="User" width="32px">
                    </div>
                    |<image id="modal-trigger" src="../images/logos/categories.png" width="30px"
                        style="cursor: pointer;"></image>
                </div>
            </header>

            <!-- DASHBOARD -->
            <div class="dash" id="dashboard">

                <div class="stat-card">
                    <img src="../images/logos/p1.png" alt="Study Rooms">
                    <div class="stat-card-content">
                        <h2 id="study-rooms"><?php
                        // Database connection parameters
                        $host = "localhost";
                        $user = "root";
                        $pass = "";
                        $db = "medstudy";

                        // Function to safely execute a query and return a single value
                        function fetchSingleValue($conn, $query) {
                            $result = $conn->query($query);
                            return $result ? $result->fetch_assoc()['value'] : 0;
                        }

                        // Establish database connection
                        $conn = new mysqli($host, $user, $pass, $db);
                        if ($conn->connect_error) {
                            die("Connection failed: " . $conn->connect_error);
                        }

                        // Queries for dashboard statistics
                        $total_rooms_query = "SELECT COUNT(*) as value FROM rooms";
                        $upcoming_bookings_query = "SELECT COUNT(*) as value FROM booking WHERE status = 'in_process'";
                        $current_time = date('Y-m-d H:i:s');
                        $occupied_rooms_query = "
                            SELECT COUNT(DISTINCT b.room_id) as value 
                            FROM booking b 
                            JOIN rooms r ON b.room_id = r.room_id 
                            WHERE b.status = 'completed' 
                            AND b.start_time <= '$current_time' 
                            AND b.end_time >= '$current_time'
                        ";

                        // Fetch statistics
                        $total_rooms = fetchSingleValue($conn, $total_rooms_query);
                        $upcoming_bookings = fetchSingleValue($conn, $upcoming_bookings_query);
                        $occupied_rooms = fetchSingleValue($conn, $occupied_rooms_query);

                        $conn->close();
                        echo $total_rooms;
                        ?></h2>
                        <p>Study Rooms</p>
                    </div>
                </div>
                <div class="stat-card">
                    <img src="../images/logos/p2.png" alt="Upcoming Bookings">
                    <div class="stat-card-content">
                        <h2 id="bookings"><?php echo $upcoming_bookings; ?></h2>
                        <p>Upcoming Bookings</p>
                    </div>
                </div>
                <div class="stat-card">
                    <img src="../images/logos/p3.png" alt="Occupied Rooms">
                    <div class="stat-card-content">
                        <h2 id="occupied-rooms"><?php echo $occupied_rooms; ?></h2>
                        <p>Occupied Rooms</p>
                    </div>
                </div>
                <div class="stat-card">
                    <img src="../images/logos/users.png" alt="Total Users">
                    <div class="stat-card-content">
                        <h2 id="total-users"><?php
                        // Database connection parameters
                        $host = "localhost";
                        $user = "root";
                        $pass = "";
                        $db = "medstudy";

                        // Establish database connection
                        $conn = new mysqli($host, $user, $pass, $db);
                        if ($conn->connect_error) {
                            die("Connection failed: " . $conn->connect_error);
                        }

                        // Query to count total users
                        $total_users_query = "SELECT COUNT(*) as total_users FROM user_accounts";

                        $result = $conn->query($total_users_query);
                        $total_users = 0;

                        if ($result) {
                            $row = $result->fetch_assoc();
                            $total_users = $row['total_users'];
                        } else {
                            error_log("Total Users Query Failed: " . $conn->error);
                        }

                        $conn->close();
                        echo $total_users;
                        ?></h2>
                        <p>Total Users</p>
                    </div>
                </div>
        
                <div class="real-time-activity-container">
                    <div class="activity-header">
                        <h2>Real-Time Activity</h2>
                        <div class="activity-header-actions">
                            <button id="refresh-activity" title="Refresh">⟳</button>
                            <button id="see-all-activity" title="See All">📋</button>
                        </div>
                    </div>
                    <div class="activity-list" id="activity-list">
                        <?php 
                        // Database connection
                        $host = "localhost";
                        $user = "root";
                        $pass = "";
                        $db = "medstudy";

                        // Establish database connection
                        $conn = new mysqli($host, $user, $pass, $db);
                        if ($conn->connect_error) {
                            die("Connection failed: " . $conn->connect_error);
                        }

                        // Fetch recent real-time activities with user details
                        $notifications_query = "
                            SELECT 
                                rta.activity_id,
                                rta.type,
                                rta.message,
                                rta.created_at,
                                ud.first_name,
                                ud.last_name,
                                rta.room_name
                            FROM 
                                real_time_activity rta
                            JOIN 
                                user_details ud ON rta.user_id = ud.user_id
                            ORDER BY 
                                rta.created_at DESC
                            LIMIT 10
                        ";

                        $result = $conn->query($notifications_query);

                        if ($result && $result->num_rows > 0) {
                            while ($notification = $result->fetch_assoc()) {
                                // Determine icon based on notification type
                                $icon = match($notification['type']) {
                                    'booking_success' => '📅',
                                    'booking_cancelled' => '❌',
                                    'admin_cancelled' => '🚫',
                                    'no_show' => '⏰',
                                    default => '📢'
                                };

                                // Format notification message
                                $full_name = htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']);
                                $room_name = htmlspecialchars($notification['room_name'] ?? 'Unknown Room');
                                $message = match($notification['type']) {
                                    'booking_success' => "$full_name booked $room_name",
                                    'booking_cancelled' => "$full_name cancelled booking",
                                    'admin_cancelled' => "Booking for $full_name cancelled by admin",
                                    'no_show' => "$full_name missed a booking",
                                    default => $notification['message']
                                };

                                echo "<div class='activity-item' data-activity-id='{$notification['activity_id']}' data-notification-type='{$notification['type']}'>
                                        <span class='icon'>{$icon}</span>
                                        <span class='message'>{$message}</span>
                                        <span class='timestamp'>" . 
                                        date('H:i', strtotime($notification['created_at'])) . 
                                        "</span>
                                      </div>";
                            }
                        } else {
                            echo "<div class='activity-item'>No recent activities</div>";
                        }

                        $conn->close();
                        ?>
                    </div>
                </div>

                <br>
                <br>
            </div>
        </div>
    </div>

    <div class="modal" id="profileModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <button class="close-btn">×</button>
                <img src="../../User/images/icons/profile.png" alt="Profile">
                <h2>Admin</h2>
            </div>
            <div class="modal-footer">
                <button class="logout-btn">Log out</button>
            </div>
        </div>
    </div>

    <div class="popup" id="loadingPopup" style="display: none;">
        <div class="popup-content">
            <p>Please Wait a Moment....</p>
            <img src="../../User/images/logos/medstudyLogo.png" alt="Loading" class="loading-image">
        </div>
    </div>

    <!-- Defer script loading -->
    <script src="../js/dashboard.js" defer></script>
    
    <!-- Debugging script -->
    <script>
        console.log('Dashboard page finished loading');
        
        // Attach event listeners after DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM fully loaded and parsed');
            
            // Debug button existence
            const refreshButton = document.getElementById('refresh-activity');
            const seeAllButton = document.getElementById('see-all-activity');
            const modalTrigger = document.getElementById('modal-trigger');
            
            console.log('Button check:', {
                refreshButton: !!refreshButton,
                seeAllButton: !!seeAllButton,
                modalTrigger: !!modalTrigger
            });
        });
    </script>
</body>
</html>