<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs | Admin</title>
   
    <link rel="icon" href="../../User/images\logos\medstudyLogo.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .activity-logs-container {
            max-width: 900px;
            margin: 20px auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .activity-logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .activity-logs-header h1 {
            margin: 0;
            color: #007bff;
            font-size: 24px;
        }

        .filter-section {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-section select, .filter-section input {
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="activity-logs-container">
        <div class="activity-logs-header">
            <h1>Activity Logs</h1>
            <div class="filter-section">
                <select id="type-filter">
                    <option value="">All Types</option>
                    <option value="booking_success">Bookings</option>
                    <option value="booking_cancelled">Cancellations</option>
                    <option value="admin_cancelled">Admin Cancellations</option>
                    <option value="no_show">No Shows</option>
                </select>
                <input type="date" id="date-filter" placeholder="Filter by Date">
            </div>
        </div>

        <div class="activity-list" id="activity-logs-list">
            <?php 
            // Database connection
            $host = "localhost";
            $user = "root";
            $pass = "";
            $db = "medstudy";

            // Pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $records_per_page = 20;
            $offset = ($page - 1) * $records_per_page;

            // Establish database connection
            $conn = new mysqli($host, $user, $pass, $db);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Base query for total count and records
            $base_query = "
                SELECT 
                    un.notification_id,
                    un.type,
                    un.message,
                    un.created_at,
                    ud.first_name,
                    ud.last_name,
                    r.room_name
                FROM 
                    user_notifications un
                JOIN 
                    user_details ud ON un.user_id = ud.user_id
                LEFT JOIN 
                    booking b ON un.booking_id = b.book_id
                LEFT JOIN 
                    rooms r ON b.room_id = r.room_id
            ";

            // Count total records
            $count_query = "SELECT COUNT(*) as total FROM user_notifications";
            $count_result = $conn->query($count_query);
            $total_records = $count_result->fetch_assoc()['total'];
            $total_pages = ceil($total_records / $records_per_page);

            // Fetch notifications with pagination
            $notifications_query = $base_query . "
                ORDER BY 
                    un.created_at DESC
                LIMIT $records_per_page OFFSET $offset
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

                    echo "<div class='activity-item' data-notification-id='{$notification['notification_id']}' data-notification-type='{$notification['type']}'>
                            <span class='icon'>{$icon}</span>
                            <span class='message'>{$message}</span>
                            <span class='timestamp'>" . 
                            date('Y-m-d H:i', strtotime($notification['created_at'])) . 
                            "</span>
                          </div>";
                }
            } else {
                echo "<div class='activity-item'>No activity logs found</div>";
            }

            $conn->close();
            ?>
        </div>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <button onclick="window.location.href='?page=<?php echo $page - 1; ?>'">Previous</button>
            <?php endif; ?>

            <?php if ($page < $total_pages): ?>
                <button onclick="window.location.href='?page=<?php echo $page + 1; ?>'">Next</button>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filter functionality (to be implemented)
        document.getElementById('type-filter').addEventListener('change', function() {
            const selectedType = this.value;
            const activityItems = document.querySelectorAll('.activity-item');
            
            activityItems.forEach(item => {
                if (selectedType === '' || item.dataset.notificationType === selectedType) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        document.getElementById('date-filter').addEventListener('change', function() {
            const selectedDate = this.value;
            const activityItems = document.querySelectorAll('.activity-item');
            
            activityItems.forEach(item => {
                const itemDate = item.querySelector('.timestamp').textContent.split(' ')[0];
                if (selectedDate === '' || itemDate === selectedDate) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 