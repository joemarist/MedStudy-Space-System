<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Admin</title>
    <link rel="icon" href="../../User/images\logos\medstudyLogo.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="../images/logos/adminIcon.png" alt="Admin Icon" width="100%">
            <hr>
            <ul>
                <li data-nav="1" onclick="titlechanges(1, this)">
                    <img src="../../User/images/icons/dashboard.png" alt="Dashboard" width="20px">
                    <span>Dashboard</span>
                </li>
                <li data-nav="2" onclick="titlechanges(2, this)">
                    <img src="../../User/images/icons/room.png" alt="Room" width="20px">
                    <span>Room</span>
                </li>
                <li data-nav="3" onclick="titlechanges(3, this)">
                    <img src="../../User/images/icons/appointment.png" alt="Booking" width="20px">
                    <span>Booking</span>
                </li>
                <li data-nav="4" class="pok active" onclick="titlechanges(4, this)">
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
                <h2 id="titlename">Reports</h2>
                <div class="header-right">
                    <div class="user-avatar">
                        <img src="../../User/images/icons/profile.png" alt="User" width="32px">
                    </div>
                    |<image onclick="openModal()" src="../images/logos/categories.png" width="30px"
                        style="cursor: pointer;"></image>
                </div>
            </header>

            <div class="reports1" id="reports">
                <div class="report_container">
                    <!-- Total Reservations Chart -->
                    <div class="report_chart_section">
                        <h2 class="report_title">Total Reservations</h2>
                        <div class="report_filters">
                            <div class="filter-group">
                                <label for="report_type">Report Type:</label>
                                <select id="report_type">
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            
                            <div class="filter-group" id="year_filter">
                                <label for="report_year">Year:</label>
                                <select id="report_year">
                                    <?php 
                                    $current_year = date('Y');
                                    for ($year = $current_year; $year >= $current_year - 5; $year--) {
                                        echo "<option value='$year'" . ($year == $current_year ? " selected" : "") . ">$year</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="filter-group" id="month_filter" style="display: none;">
                                <label for="report_month">Month:</label>
                                <select id="report_month">
                                    <?php 
                                    $months = [
                                        1 => 'January', 2 => 'February', 3 => 'March', 
                                        4 => 'April', 5 => 'May', 6 => 'June', 
                                        7 => 'July', 8 => 'August', 9 => 'September', 
                                        10 => 'October', 11 => 'November', 12 => 'December'
                                    ];
                                    $current_month = date('n');
                                    foreach ($months as $num => $name) {
                                        echo "<option value='$num'" . ($num == $current_month ? " selected" : "") . ">$name</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="filter-group" id="date_range_filter" style="display: none;">
                                <label for="start_date">From:</label>
                                <input type="date" id="start_date">
                                <label for="end_date">To:</label>
                                <input type="date" id="end_date">
                            </div>
                            
                            <div class="filter-group">
                                <button id="apply_report_filter" class="filter-btn">Apply Filter</button>
                                <button id="download_report_csv" class="download-btn">
                                    <i class="fas fa-download"></i> Download CSV
                                </button>
                            </div>
                        </div>
                        
                        <div class="report_summary">
                            <div class="summary-card">
                                <h4>Total Reservations</h4>
                                <p id="total_reservations">0</p>
                            </div>
                            <div class="summary-card">
                                <h4>Cancelled Bookings</h4>
                                <p id="cancelled_bookings">0</p>
                            </div>
                            <div class="summary-card">
                                <h4>No Shows</h4>
                                <p id="no_show_bookings">0</p>
                            </div>
                        </div>
                        
                        <canvas id="report_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="profileModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <button class="close-btn" onclick="closeModal()">×</button>
                <img src="../../User/images/icons/profile.png" alt="Profile">
                <h2>Admin</h2>
            </div>
            <div class="modal-footer">
                <button class="logout-btn" onclick="showLoading('loginAdmin.php')">Log out</button>
            </div>
        </div>
    </div>

    <div class="popup" id="loadingPopup" style="display: none;">
        <div class="popup-content">
            <p>Please Wait a Moment....</p>
            <img src="../../User/images/logos/medstudyLogo.png" alt="Loading" class="loading-image">
        </div>
    </div>

    <script>
        // Modal functions
        function openModal() {
            const profileModal = document.getElementById('profileModal');
            if (profileModal) {
                profileModal.style.display = 'flex';
            }
        }

        function closeModal() {
            const profileModal = document.getElementById('profileModal');
            if (profileModal) {
                profileModal.style.display = 'none';
            }
        }

        function showLoading(redirectUrl) {
            const loadingPopup = document.getElementById('loadingPopup');
            if (loadingPopup) {
                loadingPopup.style.display = 'flex';
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 3000);
            }
        }
    </script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/rooms.js"></script>
    <script src="../js/booking.js"></script>
    <script src="../js/reports.js"></script>
</body>
</html>
