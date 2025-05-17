<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../php/rooms_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Rooms | Admin</title>
    <link rel="icon" href="../../User/images/logos/medstudyLogo.png">
    <link rel="stylesheet" href="../css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/room.css?v=<?php echo time(); ?>">
    <style>
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .popup-content {
            background-color: white;
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .loading-image {
            width: 100px;
            height: 100px;
            margin-top: 20px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <br><br>
            <img src="../images/logos/adminIcon.png" width="100%">
            <br><br>
            <hr>
            <ul>
                <br><br>
                <li onclick="titlechanges(1,this)">
                    <img src="../../User/images/icons/dashboard.png" width="20px">
                    <span style="margin-left: 5px;">Dashboard</span>
                </li>
                <li class="pok" onclick="titlechanges(2,this)">
                    <img src="../../User/images/icons/room.png" width="20px">
                    <span style="margin-left: 5px;">Room</span>
                </li>
                <li onclick="titlechanges(3,this)">
                    <img src="../../User/images/icons/appointment.png" width="20px">
                    <span style="margin-left: 5px;">Booking</span>
                </li>
                <li onclick="titlechanges(4,this)">
                    <img src="../../User/images/icons/bar-chart.png" width="20px">
                    <span style="margin-left: 5px;">Reports</span>
                </li>
            </ul>
            <br>
            <hr>
            <br><br><br><br><br><br><br><br><br><br><br>
            <img src="../images/logos/usep.png" width="100%">
        </aside>
    </div>

    <div class="box2">
        <div class="main-container">
            <header class="header">
                <h2 id="titlename">Rooms</h2>
                <div class="header-right">
                    <div class="user-avatar">
                        <img src="../../User/images/icons/profile.png" alt="User" width="32px">
                    </div>
                    |<img onclick="openModal()" src="../images/logos/categories.png" width="30px" style="cursor: pointer;">
                </div>
            </header>

            <div class="rooms-contain" id="rooms">
                <button id="room_popup_open" class="room-button" title="Add Rooms">
                    <img src="../images/logos/addroombutton.png" width="150px">
                </button>
                <br><br><br><br><br>
                <?php if (empty($rooms)): ?>
                    <p class="no-rooms">No rooms found. Add a room to get started.</p>
                <?php else: ?>
                    <div class="rooms-list" id="rooms-list">
                        <?php foreach ($rooms as $room): ?>
                            <div class="room-holder" id="room_<?php echo htmlspecialchars($room['room_id']); ?>" data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>">
                                <div class="r1">
                                    <img class="room-image" src="<?php echo $room['room_image_base64'] ? 'data:image/png;base64,' . $room['room_image_base64'] : '../images/logos/studyroom.png'; ?>" width="100%" alt="Room Image">
                                </div>
                                <div class="r2">
                                    <h1 class="des room-name"><?php echo htmlspecialchars($room['room_name']); ?></h1>
                                    <br>
                                    <table>
                                        <tr>
                                            <td><img src="../images/people.png" width="40px" alt="Students"></td>
                                            <td><h4 class="des"><b class="student-capacity"><?php echo htmlspecialchars($room['student_capacity']); ?></b> Students</h4></td>
                                        </tr>
                                        <tr>
                                            <td><img src="../images/logos/chair.png" width="40px" alt="Chairs"></td>
                                            <td><h4 class="des"><b class="chairs-count"><?php echo htmlspecialchars($room['chairs']); ?></b> Chairs</h4></td>
                                        </tr>
                                        <tr>
                                            <td><img src="../images/logos/table.png" width="40px" alt="Tables"></td>
                                            <td><h4 class="des"><b class="tables-count"><?php echo htmlspecialchars($room['tables']); ?></b> Table</h4></td>
                                        </tr>
                                        <tr>
                                            <td><img src="../images/icons/key.png" width="40px" alt="Room Key"></td>
                                            <td><h4 class="des"><b class="room-key"><?php echo htmlspecialchars($room['room_key']); ?></b></h4></td>
                                        </tr>
                                    </table>
                                    <div class="qr-code-container">
                                        <img src="<?php echo $room['qr_code_base64'] ? 'data:image/png;base64,' . $room['qr_code_base64'] : '../images/logos/studyroom.png'; ?>" class="qr-code-img" alt="QR Code">
                                        <button class="download-qr-btn" onclick="downloadQrCode(<?php echo htmlspecialchars($room['room_id']); ?>)">Download QR Code</button>
                                    </div>
                                </div>
                                <div class="r3">
                                    <button class="ts room-edit-btn" data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" onclick="handleEditButtonClick(event)">
                                        <img src="../images/logos/edit.png" width="30px" alt="Edit Room" data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>">
                                    </button>
                                    <br><br><br><br><br><br><br><br><br><br>
                                                                                                            <div align="right">                                        <h3 class="room-status" data-status="<?php echo htmlspecialchars($room['status']); ?>"><?php echo htmlspecialchars($room['status']); ?></h3>                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="room_popup_container" class="room_popup_overlay">
        <div class="room_popup_box">
            <div class="room_popup_image_section">
                <img id="room_popup_image_preview" src="../images/logos/studyroom.png" alt="Room Image">
                <span id="room_popup_upload" class="room_popup_upload_btn">Upload new photo</span>
            </div>
            <div class="room_popup_details">
                <h2>Register Room</h2>
                <form id="room_popup_form" action="../php/addRoom.php" method="POST" enctype="multipart/form-data" class="room-form">
                    <input type="file" id="room_popup_image_input" name="room_image" accept="image/png,image/jpeg" required style="display: none;">
                    <div class="form-group">
                        <label for="room_popup_name">Room Name:</label>
                        <input type="text" id="room_popup_name" name="room_name" placeholder="Enter Room Name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="room_popup_students">Number of Students:</label>
                        <input type="number" id="room_popup_students" name="student_capacity" placeholder="Enter Number of Students" min="1" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="room_popup_chairs">Number of Chairs:</label>
                        <input type="number" id="room_popup_chairs" name="chairs" placeholder="Enter Number of Chairs" min="1" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="room_popup_tables">Number of Tables:</label>
                        <input type="number" id="room_popup_tables" name="tables" placeholder="Enter Number of Tables" min="1" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="room_popup_status">Room Status:</label>
                        <select id="room_popup_status" name="status" required class="form-control">
                            <option value="" disabled selected>Select Status</option>
                            <option value="Available">Available</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Not Available">Not Available</option>
                        </select>
                    </div>
                    <input type="hidden" id="file_selected" name="file_selected" value="">
                    <div class="room_popup_buttons">
                        <button type="button" id="room_popup_close" class="room_popup_cancel">Cancel</button>
                        <button type="submit" id="room_popup_create" class="room_popup_create">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="room-edit-overlay" class="room-edit-overlay">
        <div class="room-edit-popup">
            <div class="room-edit-image-container">
                <img id="edit_room_image" src="../images/logos/studyroom.png" alt="Room Image">                <span class="room-edit-upload-btn">Upload new photo</span>                <div class="qr-code-container">
                    <img id="edit_qr_code" class="qr-code-img" alt="QR Code">
                    <button class="download-qr-btn" id="edit_download_qr_btn">Download QR Code</button>
                </div>
            </div>
            <div class="room-edit-form-container">
                <div class="room-edit-header">
                    <span>Edit Room</span>
                    <button id="room-edit-close" class="room-edit-close">&times;</button>
                </div>
                <form id="room_edit_form" action="../php/updateRoom.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="edit_room_id" name="room_id" required>
                    <input type="file" id="edit_room_image_input" name="room_image" accept="image/png,image/jpeg" style="display: none;">
                    <div class="form-group">
                        <label for="roomid_input">Room Name:</label>
                        <input type="text" id="roomid_input" name="room_name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="student_id">Number of Students:</label>
                        <input type="number" id="student_id" name="student_capacity" min="1" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="chair_id">Number of Chairs:</label>
                        <input type="number" id="chair_id" name="chairs" min="1" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="table_id">Number of Tables:</label>
                        <input type="number" id="table_id" name="tables" min="1" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="choices">Room Status:</label>
                        <select id="choices" name="status" required class="form-control">
                            <option value="Available">Available</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Not Available">Not Available</option>
                        </select>
                    </div>
                    <div class="room-edit-footer">
                        <button type="button" class="room-edit-btn room-edit-btn-delete" id="room-edit-delete">Delete</button>
                        <button type="submit" class="room-edit-btn room-edit-btn-update" id="room-edit-update">Update</button>
                    </div>
                </form>
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

    <div id="pop_up_delete_confirmation_overlay" class="pop_up_delete_confirmation_overlay" style="display: none;">
        <div class="pop_up_delete_confirmation_box">
            <p>Do you want to delete?</p>
            <form id="delete_room_form" action="../php/deleteRoom.php" method="POST">
                <input type="hidden" id="delete_room_id" name="room_id">
                <div class="pop_up_delete_confirmation_buttons">
                    <button type="button" id="pop_up_delete_cancel_button" class="pop_up_delete_confirmation_button">No</button>
                    <button type="submit" id="pop_up_delete_confirm_button" class="pop_up_delete_confirmation_button">Yes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.roomsData = <?php echo json_encode($rooms); ?>;
        console.log("[rooms] roomsData initialized:", window.roomsData);

        function openModal() {
            document.getElementById("profileModal").style.display = "flex";
        }

        function closeModal() {
            document.getElementById("profileModal").style.display = "none";
        }

        function showLoading(redirectUrl) {
            closeModal();
            document.getElementById('loadingPopup').style.display = 'flex';
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 3000);
        }
    </script>
    <script src="../js/rooms.js?v=<?php echo time(); ?>"></script>
    <script src="../js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>