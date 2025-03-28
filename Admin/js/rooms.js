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
        } else {
            statusLabel.innerText = "Occupied";
            statusLabel.style.color = "red";
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