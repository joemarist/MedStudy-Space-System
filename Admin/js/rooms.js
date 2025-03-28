document.addEventListener("DOMContentLoaded", function () {
    const modalOverlay = document.getElementById("room-edit-overlay");
    const openModalBtn = document.getElementById("room-edit-open");
    const closeModalBtn = document.getElementById("room-edit-close");
    const updateBtn = document.querySelector(".room-edit-btn-update");
    const statusSelect = document.getElementById("choices");
    const statusLabel = document.getElementById("status");

    // Inputs inside modal
    const roomNameInput = document.getElementById("roomid_input");
    const studentInput = document.getElementById("student_id");
    const chairInput = document.getElementById("chair_id");
    const tableInput = document.getElementById("table_id");

    // Elements to update on the main page
    const roomNameLabel = document.getElementById("roomid");
    const studentLabel = document.getElementById("student_num");
    const chairLabel = document.getElementById("chair_num");
    const tableLabel = document.getElementById("table_num");

    // Image Upload Elements
    const uploadButton = document.querySelector(".room-edit-upload-btn");
    const imagePreview = document.querySelector(".room-edit-image-container img");
    const bigPic = document.getElementById("bigpic"); // The image displayed in main content

    // Create a hidden file input
    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.accept = "image/*"; // Accept only image files
    fileInput.style.display = "none";

    // Append file input to the body
    document.body.appendChild(fileInput);

    // Open Modal & Pre-fill values
    openModalBtn.addEventListener("click", function () {
        modalOverlay.classList.add("active");

        // Set input values from the displayed elements
        roomNameInput.value = roomNameLabel.innerText;
        studentInput.value = studentLabel.innerText;
        chairInput.value = chairLabel.innerText;
        tableInput.value = tableLabel.innerText;
    });

    // Close Modal
    closeModalBtn.addEventListener("click", function () {
        modalOverlay.classList.remove("active");
    });

    window.addEventListener("click", function (event) {
        if (event.target === modalOverlay) {
            modalOverlay.classList.remove("active");
        }
    });

    // Update Room Information
    updateBtn.addEventListener("click", function () {
        // Update the text content of displayed elements
        roomNameLabel.innerText = roomNameInput.value;
        studentLabel.innerText = studentInput.value;
        chairLabel.innerText = chairInput.value;
        tableLabel.innerText = tableInput.value;

        // Update status text
        if (statusSelect.value === "Available") {
            statusLabel.innerText = "Available";
            statusLabel.style.color = "green";
        } else if(statusSelect.value ==="Not Available"){
            statusLabel.innerText = "Not Available";
            statusLabel.style.color = "red";
        }else{
            statusLabel.innerText = "Maintenancee";
            statusLabel.style.color = "orange";
        }

        // Close the pop-up after updating
        modalOverlay.classList.remove("active");
    });

    // Handle Image Upload
    uploadButton.addEventListener("click", function () {
        fileInput.click(); // Trigger the file input dialog
    });

    fileInput.addEventListener("change", function (event) {
        const file = event.target.files[0];

        if (file) {
            const reader = new FileReader();

            reader.onload = function (e) {
                // Update both the modal preview and the main displayed image
                imagePreview.src = e.target.result;
                bigPic.src = e.target.result;
            };

            reader.readAsDataURL(file);
        }
    });
});
































document.addEventListener("DOMContentLoaded", function () {
    const popup = document.getElementById("room_popup_container");
    const openBtn = document.getElementById("room_popup_open");
    const closeBtn = document.getElementById("room_popup_close");
    const createBtn = document.getElementById("room_popup_create");
    const uploadBtn = document.getElementById("room_popup_upload");
    const imagePreview = document.getElementById("room_popup_image_preview");

    // Create a hidden file input
    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.accept = "image/*"; 
    fileInput.style.display = "none";
    document.body.appendChild(fileInput);

    // Open the Popup
    openBtn.addEventListener("click", function () {
        popup.style.display = "flex";
    });

    // Close the Popup
    closeBtn.addEventListener("click", function () {
        popup.style.display = "none";
    });

    // Close when clicking outside
    window.addEventListener("click", function (event) {
        if (event.target === popup) {
            popup.style.display = "none";
        }
    });

    // Handle Image Upload
    uploadBtn.addEventListener("click", function () {
        fileInput.click(); 
    });

    fileInput.addEventListener("change", function (event) {
        const file = event.target.files[0];

        if (file) {
            const reader = new FileReader();

            reader.onload = function (e) {
                imagePreview.src = e.target.result; 
            };

            reader.readAsDataURL(file);
        }
    });

    // Handle Create Button
    createBtn.addEventListener("click", function () {
        // Get input values
        const newName = document.getElementById("room_popup_name").value;
        const newStudents = document.getElementById("room_popup_students").value;
        const newChairs = document.getElementById("room_popup_chairs").value;
        const newTables = document.getElementById("room_popup_tables").value;
        const newStatus = document.getElementById("room_popup_status").value;
        const newRoomKey = document.getElementById("room_popup_key").value;

        // Log the values (Replace with your actual logic)
        console.log("Room Created:");
        console.log("Name:", newName);
        console.log("Students:", newStudents);
        console.log("Chairs:", newChairs);
        console.log("Tables:", newTables);
        console.log("Status:", newStatus);
        console.log("Room Key:", newRoomKey);

        // Close Modal
        popup.style.display = "none";
    });
});








document.addEventListener("DOMContentLoaded", function () {
    const createBtn = document.getElementById("room_popup_create");
    const roomsContainer = document.getElementById("rooms");
    const imagePreview = document.getElementById("room_popup_image_preview");

    createBtn.addEventListener("click", function () {
        // Get input values
        const roomName = document.getElementById("room_popup_name").value || "New Room";
        const studentNum = document.getElementById("room_popup_students").value || "0";
        const chairNum = document.getElementById("room_popup_chairs").value || "0";
        const tableNum = document.getElementById("room_popup_tables").value || "0";
        const status = document.getElementById("room_popup_status").value || "Available";
        const roomImageSrc = imagePreview.src;

        // Create the new room div
        const newRoom = document.createElement("div");
        newRoom.classList.add("room-holder");

        // Add inner HTML structure
        newRoom.innerHTML = `
            <div class="r1">
                <img id="bigpic" src="${roomImageSrc}" width="100%">
            </div>

            <div class="r2">
                <h1 class="des">${roomName}</h1>
                <br>
                <table>
                    <tr>
                        <td><img src="../images/people.png" width="40px"></td>
                        <td><h4 class="des"><b>${studentNum}</b> Students</h4></td>
                    </tr>
                    <tr>
                        <td><img src="../images/logos/chair.png" width="40px"></td>
                        <td><h4 class="des"><b>${chairNum}</b> Chairs</h4></td>
                    </tr>
                    <tr>
                        <td><img src="../images/logos/table.png" width="40px"></td>
                        <td><h4 class="des"><b>${tableNum}</b> Table</h4></td>
                    </tr>
                </table>
            </div>

            <div class="r3">
                <div class="ts">
                    <img src="../images/logos/edit.png" width="30px">
                </div>
                <br><br><br><br><br><br><br><br><br><br>
                <div align="right">
                    <h3 id="status">${status}</h3>
                </div>
            </div>
        `

        // Append the new room to the container
        roomsContainer.appendChild(newRoom);

        // Close the popup after creation
        document.getElementById("room_popup").style.display = "none";
    });
});


document.getElementById("room_popup_image_input").addEventListener("change", function (event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function () {
            document.getElementById("room_popup_image_preview").src = reader.result;
        };
        reader.readAsDataURL(file);
    }
});