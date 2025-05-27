<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management | MedStudy Space</title>
    <link rel="icon" href="../../User/images\logos\medstudyLogo.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/room.css">
    <link rel="stylesheet" href="../css/booking.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Enhanced Design */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --background-light: #f4f6f7;
            --text-dark: #2c3e50;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--background-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .search-filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }

        .search-filter-container input, 
        .search-filter-container select {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            transition: all 0.3s ease;
            flex-grow: 1;
        }

        .search-filter-container input:focus, 
        .search-filter-container select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .booking_popup_main_container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .booking_popup_card {
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            position: relative;
        }

        .booking_popup_card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .booking_popup_profile {
            width: 100%;
            height: 280px;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
        }

        .booking_popup_card:hover .booking_popup_profile {
            transform: scale(1.05);
        }

        .booking_popup_info {
            padding: 15px;
            background-color: white;
            position: relative;
        }

        .booking_popup_info::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(rgba(0,0,0,0.05), transparent);
        }

        .booking_popup_info h3 {
            margin: 0 0 10px 0;
            color: var(--primary-color);
            font-size: 1.2em;
            font-weight: 600;
        }

        .booking_popup_info p {
            margin: 5px 0;
            color: #7f8c8d;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .booking_popup_info p i {
            color: var(--primary-color);
            margin-right: 8px;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-in_process {
            background-color: #3498db;
            color: white;
        }

        .status-cancelled {
            background-color: #e74c3c;
            color: white;
        }

        .status-completed {
            background-color: #2ecc71;
            color: white;
        }

        /* Cancel Reason Popup Styling */
        #cancel_reason_popup .cancel_popup_content {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            max-width: 500px;
            margin: auto;
        }

        #cancel_reason_popup .cancel_reason_options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 20px 0;
        }

        #cancel_reason_popup .cancel_reason_options label {
            display: flex;
            align-items: center;
            background-color: var(--background-light);
            padding: 15px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        #cancel_reason_popup .cancel_reason_options label:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        #cancel_reason_popup .cancel_popup_buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .cancel_no, .cancel_yes {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .cancel_no {
            background-color: #e74c3c;
            color: white;
        }

        .cancel_yes {
            background-color: var(--secondary-color);
            color: white;
        }

        @media (max-width: 768px) {
            .booking_popup_main_container {
                grid-template-columns: 1fr;
            }

            .search-filter-container {
                flex-direction: column;
                gap: 10px;
            }

            .search-filter-container input, 
            .search-filter-container select {
                width: 100%;
                margin-right: 0;
            }
        }

        /* Enhanced Booking Card Styling */
        .booking_popup_card {
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .booking_popup_card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .booking_popup_info {
            position: relative;
            padding-bottom: 40px; /* Extra space for status */
        }

        .booking_status_badge {
            position: absolute;
            bottom: 10px;
            left: 15px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-in_process {
            background-color: #3498db;
            color: white;
        }

        .status-completed {
            background-color: #2ecc71;
            color: white;
        }

        .status-cancelled {
            background-color: #e74c3c;
            color: white;
        }

        /* Improved Popup Positioning and Z-Index */
        .booking_popup_container,
        .cancel_popup_container,
        .modal,
        #loadingPopup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* High z-index to ensure popups are on top */
        }

        .booking_popup_content,
        .cancel_popup_content,
        .modal-content,
        .popup-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000; /* Slightly higher than container */
        }

        /* Ensure cancel reason popup is clearly visible */
        #cancel_reason_popup .cancel_popup_content {
            position: relative;
            z-index: 10001;
        }

        /* Loading Popup Styling */
        #loadingPopup .popup-content {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 300px;
        }

        #loadingPopup .loading-image {
            max-width: 150px;
            margin-top: 20px;
        }

        /* Modal Improvements */
        .modal .modal-content {
            text-align: center;
            max-width: 400px;
        }

        .modal .modal-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal .modal-header img {
            width: 100px;
            margin-bottom: 15px;
        }

        .modal .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }

        /* Enhanced Booking Popup Profile Image */
        .booking_popup_profile_large {
            width: 250px;
            height: 250px;
            object-fit: cover;
            object-position: center;
            border-radius: 12px;
            margin: 0 auto 20px;
            display: block;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .booking_popup_body {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .booking_popup_text {
            width: 100%;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px;
            align-items: center;
        }

        .booking_popup_text label {
            font-weight: bold;
            color: #2c3e50;
            text-align: right;
        }

        .booking_popup_text input {
            width: 100%;
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background-color: #f9f9f9;
            cursor: not-allowed;
        }

        .booking_popup_footer {
            margin-top: 20px;
            text-align: center;
            background-color: #f4f6f7;
            padding: 15px;
            border-radius: 8px;
        }

        .booking_popup_footer h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .booking_popup_qr {
            max-width: 150px;
            margin: 15px auto;
            display: block;
        }

        .booking_popup_cancel {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .booking_popup_cancel:hover {
            background-color: #c0392b;
        }

        /* Responsive Booking Popup Enhancements */
        .booking_popup_container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            overflow-y: auto;
            padding: 20px;
            box-sizing: border-box;
            justify-content: center;
            align-items: center;
        }

        .booking_popup_content {
            background: white;
            border-radius: 15px;
            max-width: 800px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .booking_popup_close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f0f0f0;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .booking_popup_close:hover {
            background: #e74c3c;
            color: white;
        }

        .booking_popup_body {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            gap: 20px;
        }

        .booking_popup_profile_large {
            width: 200px;
            height: 200px;
            object-fit: cover;
            object-position: center;
            border-radius: 50%;
            border: 5px solid var(--primary-color);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .booking_popup_text {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .booking_popup_text .input-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .booking_popup_text label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .booking_popup_text input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #f9f9f9;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .booking_popup_text input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .booking_popup_footer {
            background-color: #f4f6f7;
            padding: 20px;
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            text-align: center;
        }

        .booking_popup_footer h3 {
            color: var(--primary-color);
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        #booking_times_container {
            display: flex;
            justify-content: center;
            gap: 20px;
            width: 100%;
        }

        #booking_times_container p {
            background-color: #e8f4f8;
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        #booking_times_container p i {
            color: var(--primary-color);
        }

        .booking_popup_qr {
            max-width: 150px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .booking_popup_cancel {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .booking_popup_cancel:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .booking_popup_content {
                width: 95%;
                margin: 20px auto;
            }

            .booking_popup_body {
                padding: 15px;
            }

            .booking_popup_text {
                grid-template-columns: 1fr;
            }

            #booking_times_container {
                flex-direction: column;
                align-items: center;
            }

            .booking_popup_profile_large {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="../images/logos/adminIcon.png" alt="Admin Icon" width="100%">
            <hr>
            <ul>
                <li onclick="titlechanges(1, this)">
                    <img src="../../User/images/icons/dashboard.png" alt="Dashboard" width="20px">
                    <span>Dashboard</span>
                </li>
                <li onclick="titlechanges(2, this)">
                    <img src="../../User/images/icons/room.png" alt="Room" width="20px">
                    <span>Room</span>
                </li>
                <li class="pok active" onclick="titlechanges(3, this)">
                    <img src="../../User/images/icons/appointment.png" alt="Booking" width="20px">
                    <span>Booking</span>
                </li>
                <li onclick="titlechanges(4, this)">
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
                <h2 id="titlename">Booking</h2>
                <div class="header-right">
                    <div class="user-avatar">
                        <img src="../../User/images/icons/profile.png" alt="User" width="32px">
                    </div>
                    |<img onclick="openModal()" src="../images/logos/categories.png" width="30px" style="cursor: pointer;">
                </div>
            </header>

            <div class="search-filter-container">
                <div class="search-inputs">
                    <input type="text" id="searchInput" placeholder="Search by Name, Room, or Status" onkeyup="filterBookings()">
                    <input type="date" id="dateFilterInput" onchange="filterBookings()">
                    <select id="roomFilterInput" onchange="filterBookings()">
                        <option value="">All Rooms</option>
                    </select>
                    <select id="statusFilterInput" onchange="filterBookings()">
                        <option value="">All Statuses</option>
                        <option value="in_process">In Process</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled by student">Cancelled by Student</option>
                        <option value="cancelled by admin">Cancelled by Admin</option>
                    </select>
                </div>
            </div>

            <div class="booking" id="books">
                <div class="booking_popup_main_container" id="bookingsContainer">
                    <div id="loadingIndicator" style="width: 100%; text-align: center; padding: 20px;">
                        <p>Loading bookings...</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="booking_popup_container" class="booking_popup_container">
            <div class="booking_popup_content">
                <button class="booking_popup_close" onclick="closeBookingPopup()">&times;</button>
                <div class="booking_popup_body">
                    <h2>Booking Details</h2>
                    <img id="booking_popup_profile_img" src="../images/logos/profile.png" alt="Profile Picture" class="booking_popup_profile_large">
                    <div class="booking_popup_text">
                        <div class="input-group">
                            <label>First Name</label>
                            <input type="text" id="popup_firstname" readonly>
                        </div>
                        <div class="input-group">
                            <label>Middle Name</label>
                            <input type="text" id="popup_middlename" readonly>
                        </div>
                        <div class="input-group">
                            <label>Last Name</label>
                            <input type="text" id="popup_lastname" readonly>
                        </div>
                        <div class="input-group">
                            <label>Contact Number</label>
                            <input type="text" id="popup_contact" readonly>
                        </div>
                        <div class="input-group">
                            <label>Email</label>
                            <input type="text" id="popup_email" readonly>
                        </div>
                        <div class="input-group">
                            <label>Booking Date</label>
                            <input type="date" id="popup_date" readonly>
                        </div>
                    </div>
                </div>
                <div class="booking_popup_footer">
                    <h3 id="popup_studyroom">Study Room Details</h3>
                    <div id="booking_times_container"></div>
                    <img src="../images/logos/QR.png" alt="QR Code" class="booking_popup_qr">
                    <p><strong>Booking ID:</strong> <span id="popup_id"></span></p>
                    <button class="booking_popup_cancel" onclick="showCancelPopup()">
                        <i class="fas fa-times-circle"></i> Cancel Booking
                    </button>
                </div>
            </div>
        </div>

        <div id="cancel_reason_popup" class="cancel_popup_container" style="display:none;">
            <div class="cancel_popup_content">
                <h2>Select Reason for Cancellation</h2>
                <div class="cancel_reason_options">
                    <label>
                        <input type="radio" name="cancel_reason" value="Sudden Maintenance"> 
                        Sudden Maintenance
                    </label>
                    <label>
                        <input type="radio" name="cancel_reason" value="Urgent use of the room"> 
                        Urgent use of the room
                    </label>
                    <label>
                        <input type="radio" name="cancel_reason" value="Room is not available"> 
                        Room is not available
                    </label>
                </div>
                <div class="cancel_popup_buttons">
                    <button class="cancel_no" onclick="closeCancelReasonPopup()">Cancel</button>
                    <button class="cancel_yes" onclick="confirmCancelBooking()">Confirm</button>
                </div>
            </div>
        </div>

        <div class="modal" id="profileModal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                    <img src="../../User/images/icons/profile.png" alt="Profile">
                    <h2>Admin</h2>
                </div>
                <div class="modal-footer">
                    <button class="logout-btn" onclick="showLoading('loginAdmin.html')">Log out</button>
                </div>
            </div>
        </div>

        <div class="popup" id="loadingPopup" style="display: none;">
            <div class="popup-content">
                <p>Please Wait a Moment....</p>
                <img src="../../User/images/logos/medstudyLogo.png" alt="Loading" class="loading-image">
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            const profileModal = document.getElementById("profileModal");
            if (profileModal) {
                profileModal.style.display = "flex";
            }
        }

        function closeModal() {
            const profileModal = document.getElementById("profileModal");
            if (profileModal) {
                profileModal.style.display = "none";
            }
        }

        function showLoading(redirectUrl) {
            // Close any open modals first
            closeModal();
            
            // Show loading popup
            const loadingPopup = document.getElementById('loadingPopup');
            if (loadingPopup) {
                loadingPopup.style.display = 'flex';
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 3000);
            }
        }

        function filterBookings() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const dateFilter = document.getElementById('dateFilterInput').value;
            const roomFilter = document.getElementById('roomFilterInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilterInput').value.toLowerCase();
            const bookingCards = document.querySelectorAll('.booking_popup_card');

            let visibleCount = 0;
            bookingCards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const room = card.dataset.room.toLowerCase();
                const date = card.dataset.date;
                const status = card.dataset.status.toLowerCase();

                const nameMatch = name.includes(searchInput);
                const roomMatch = roomFilter === '' || room.includes(roomFilter);
                const dateMatch = dateFilter === '' || date === dateFilter;
                const statusMatch = statusFilter === '' || 
                    status === statusFilter;

                const isVisible = nameMatch && roomMatch && dateMatch && statusMatch;
                card.style.display = isVisible ? 'flex' : 'none';
                
                if (isVisible) visibleCount++;
            });

            // Show/hide no results message
            const noResultsMessage = document.querySelector('.no-bookings');
            if (noResultsMessage) {
                noResultsMessage.style.display = visibleCount > 0 ? 'none' : 'block';
            }
        }

        let currentBookingToCancel = null;

        function openBookingPopup(element, firstName, middleName, lastName, contact, email, studyRoom, bookingId, date, profilePic, startTime, endTime, status) {
            document.getElementById('popup_firstname').value = firstName;
            document.getElementById('popup_middlename').value = middleName;
            document.getElementById('popup_lastname').value = lastName;
            document.getElementById('popup_contact').value = contact;
            document.getElementById('popup_email').value = email;
            document.getElementById('popup_studyroom').textContent = studyRoom;
            document.getElementById('popup_id').textContent = bookingId;
            document.getElementById('popup_date').value = date;
            
            const profileImage = document.getElementById('booking_popup_profile_img');
            profileImage.src = profilePic || '../images/logos/profile.png';
            profileImage.alt = `${firstName} ${lastName}'s Profile`;

            const timesContainer = document.getElementById('booking_times_container');
            if (timesContainer) {
                timesContainer.innerHTML = `
                    <p><strong>Start Time:</strong> ${startTime}</p>
                    <p><strong>End Time:</strong> ${endTime}</p>
                `;
            }
            
            currentBookingToCancel = {
                bookingId: bookingId,
                studyRoom: studyRoom
            };
            
            const cancelButton = document.querySelector('.booking_popup_cancel');
            if (cancelButton) {
                cancelButton.style.display = status.includes('cancelled') ? 'none' : 'block';
            }
            
            document.getElementById('booking_popup_container').style.display = 'flex';
        }

        function showCancelPopup() {
            // Close any existing popups first
            document.getElementById('booking_popup_container').style.display = 'none';
            
            // Show cancel reason popup
            const cancelReasonPopup = document.getElementById('cancel_reason_popup');
            if (cancelReasonPopup) {
                cancelReasonPopup.style.display = 'flex';
            }
        }

        function closeCancelReasonPopup() {
            const cancelReasonPopup = document.getElementById('cancel_reason_popup');
            if (cancelReasonPopup) {
                cancelReasonPopup.style.display = 'none';
            }
            
            // Reopen the booking popup
            const bookingPopupContainer = document.getElementById('booking_popup_container');
            if (bookingPopupContainer) {
                bookingPopupContainer.style.display = 'flex';
            }
        }

        function confirmCancelBooking() {
            const selectedReason = document.querySelector('input[name="cancel_reason"]:checked');
            
            if (!selectedReason) {
                alert('Please select a reason for cancellation');
                return;
            }

            // Disable buttons to prevent multiple submissions
            const confirmButton = document.querySelector('.cancel_yes');
            const cancelButton = document.querySelector('.cancel_no');
            confirmButton.disabled = true;
            cancelButton.disabled = true;

            // Log the data being sent
            console.log('Sending cancellation request:', {
                booking_id: currentBookingToCancel.bookingId,
                cancel_reason: selectedReason.value
            });

            fetch('../php/cancel_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    booking_id: currentBookingToCancel.bookingId,
                    cancel_reason: selectedReason.value
                })
            })
            .then(response => {
                // Log the raw response for debugging
                console.log('Raw response:', response);
                
                // Check if the response is ok
                if (!response.ok) {
                    // Try to get the error text for more details
                    return response.text().then(errorText => {
                        console.error('Error response text:', errorText);
                        throw new Error(`Network response was not ok. Status: ${response.status}, Text: ${errorText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the booking card's status
                    const bookingCard = document.querySelector(`.booking_popup_card[data-name*="${currentBookingToCancel.studyRoom}"]`);
                    if (bookingCard) {
                        // Update status badge
                        const statusBadge = bookingCard.querySelector('.booking_status_badge');
                        if (statusBadge) {
                            statusBadge.textContent = 'Cancelled by Admin';
                            statusBadge.className = 'booking_status_badge status-cancelled';
                        }
                        
                        // Update data attributes for filtering
                        bookingCard.setAttribute('data-status', 'cancelled by admin');
                    }
                    
                    // Close popups
                    document.getElementById('cancel_reason_popup').style.display = 'none';
                    document.getElementById('booking_popup_container').style.display = 'none';
                    
                    // Show success message
                    alert('Booking cancelled successfully');

                    // Optional: Refresh bookings to ensure latest data
                    fetchBookings();
                } else {
                    throw new Error(data.message || 'Cancellation failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cancelling the booking: ' + error.message);
            })
            .finally(() => {
                // Re-enable buttons
                confirmButton.disabled = false;
                cancelButton.disabled = false;
            });
        }

        function closeBookingPopup() {
            const bookingPopupContainer = document.getElementById('booking_popup_container');
            if (bookingPopupContainer) {
                bookingPopupContainer.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const bookingsContainer = document.getElementById('bookingsContainer');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const roomFilterInput = document.getElementById('roomFilterInput');

            function fetchBookings() {
                fetch('../php/get_bookings.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Remove loading indicator
                        loadingIndicator.style.display = 'none';

                        // Check if the request was successful
                        if (data.error) {
                            // Display detailed error message
                            const errorMessage = `
                                <div class="error-message" style="color: red; text-align: center; padding: 20px;">
                                    <h3>Failed to Load Bookings</h3>
                                    <p>${data.message || 'Unknown error occurred'}</p>
                                    ${data.error_details ? `<details>
                                        <summary>Error Details</summary>
                                        <pre>${JSON.stringify(data.error_details, null, 2)}</pre>
                                    </details>` : ''}
                                </div>
                            `;
                            
                            bookingsContainer.innerHTML = errorMessage;
                            return;
                        }

                        // Populate room filter
                        const uniqueRooms = [...new Set(data.bookings.map(booking => booking.room_name))];
                        uniqueRooms.forEach(room => {
                            const option = document.createElement('option');
                            option.value = room;
                            option.textContent = room;
                            roomFilterInput.appendChild(option);
                        });

                        // Check if no bookings
                        if (data.bookings.length === 0) {
                            bookingsContainer.innerHTML = `
                                <div class="no-bookings" style="width: 100%; text-align: center; padding: 20px;">
                                    No bookings found.
                                </div>
                            `;
                            return;
                        }

                        // Clear previous content
                        bookingsContainer.innerHTML = '';

                        // Render bookings
                        data.bookings.forEach(booking => {
                            // Process profile picture
                            const profilePic = booking.profile_pic ? 
                                `data:image/jpeg;base64,${booking.profile_pic}` : 
                                '../images/logos/profile.png';

                            const bookingCard = document.createElement('div');
                            bookingCard.className = 'booking_popup_card';
                            bookingCard.setAttribute('data-name', `${booking.first_name} ${booking.last_name}`);
                            bookingCard.setAttribute('data-room', booking.room_name);
                            bookingCard.setAttribute('data-date', booking.booking_date);
                            bookingCard.setAttribute('data-status', booking.status.toLowerCase());
                            
                            // Determine status badge class
                            const getStatusBadgeClass = (status) => {
                                const statusLower = status.toLowerCase();
                                switch(statusLower) {
                                    case 'in_process':
                                        return 'status-in_process';
                                    case 'completed':
                                        return 'status-completed';
                                    case 'cancelled by student':
                                    case 'cancelled by admin':
                                        return 'status-cancelled';
                                    default:
                                        return '';
                                }
                            };

                            bookingCard.innerHTML = `
                                <img src="${profilePic}" alt="Profile Picture" class="booking_popup_profile">
                                <div class="booking_popup_info">
                                    <h3>${booking.first_name} ${booking.last_name}</h3>
                                    <span class="booking_status_badge ${getStatusBadgeClass(booking.status)}">${booking.status}</span>
                                    <p><strong>Study Room:</strong> ${booking.room_name}</p>
                                    <p class="booking_time">
                                        <strong>Start Time:</strong> ${booking.start_time}
                                    </p>
                                    <p class="booking_time">
                                        <strong>End Time:</strong> ${booking.end_time}
                                    </p>
                                    <p><strong>Date:</strong> ${booking.booking_date}</p>
                                </div>
                            `;

                            // Add click event to open booking popup
                            bookingCard.addEventListener('click', () => {
                                openBookingPopup(
                                    bookingCard, 
                                    booking.first_name, 
                                    booking.middle_name, 
                                    booking.last_name, 
                                    booking.contact_number, 
                                    booking.email, 
                                    booking.room_name, 
                                    booking.booking_id, 
                                    booking.booking_date,
                                    profilePic,
                                    booking.start_time,
                                    booking.end_time,
                                    booking.status
                                );
                            });

                            bookingsContainer.appendChild(bookingCard);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching bookings:', error);
                        bookingsContainer.innerHTML = `
                            <div class="error-message" style="color: red; text-align: center; padding: 20px;">
                                <h3>Network Error</h3>
                                <p>Failed to load bookings. Please check your network connection.</p>
                                <details>
                                    <summary>Error Details</summary>
                                    <pre>${error.toString()}</pre>
                                </details>
                            </div>
                        `;
                    });
            }

            // Fetch bookings on page load
            fetchBookings();

            // Attach fetchBookings to global scope for refresh functionality
            window.fetchBookings = fetchBookings;
        });

        // Add missing titlechanges function
        function titlechanges(index, element) {
            // Remove active class from all list items
            const listItems = document.querySelectorAll('.sidebar ul li');
            listItems.forEach(item => item.classList.remove('active'));
            
            // Add active class to clicked item
            element.classList.add('active');

            // Define navigation paths
            const paths = [
                'dashboard.html',   // Dashboard
                'rooms.php',        // Room
                'booking.php',      // Booking (current page)
                'reports.php'      // Reports
            ];

            // Navigate to the corresponding page
            if (index >= 1 && index <= paths.length) {
                window.location.href = paths[index - 1];
            }
        }
    </script>
</body>
</html>