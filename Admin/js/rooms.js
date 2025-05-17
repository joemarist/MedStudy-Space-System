const roomPopupOpen = document.getElementById("room_popup_open");
const roomPopupContainer = document.getElementById("room_popup_container");
const roomPopupClose = document.getElementById("room_popup_close");
const roomPopupForm = document.getElementById("room_popup_form");
const roomPopupImageInput = document.getElementById("room_popup_image_input");
const roomPopupImagePreview = document.getElementById("room_popup_image_preview");
const roomPopupUpload = document.getElementById("room_popup_upload");

const roomEditOverlay = document.getElementById("room-edit-overlay");
const roomEditClose = document.getElementById("room-edit-close");
const roomEditForm = document.getElementById("room_edit_form");
const editRoomImageInput = document.getElementById("edit_room_image_input");
const editRoomImage = document.getElementById("edit_room_image");
const editRoomUploadBtn = document.querySelector(".room-edit-upload-btn");
const editRoomId = document.getElementById("edit_room_id");
const editRoomName = document.getElementById("roomid_input");
const editStudentCapacity = document.getElementById("student_id");
const editChairs = document.getElementById("chair_id");
const editTables = document.getElementById("table_id");
const editStatus = document.getElementById("choices");
const editQrCode = document.getElementById("edit_qr_code");
const editDownloadQrBtn = document.getElementById("edit_download_qr_btn");

const deleteConfirmationOverlay = document.getElementById("pop_up_delete_confirmation_overlay");
const deleteCancelButton = document.getElementById("pop_up_delete_cancel_button");
const deleteConfirmButton = document.getElementById("pop_up_delete_confirm_button");
const deleteRoomId = document.getElementById("delete_room_id");
const deleteRoomForm = document.getElementById("delete_room_form");

let currentRoomId = null;

function debugLog(message) {
    console.log(`[rooms.js] ${message}`);
}

// Function to download QR code
function downloadQrCode(roomId) {
    debugLog(`Downloading QR code for room ${roomId}`);
    const link = document.createElement('a');
    link.href = `../php/downloadQrCode.php?room_id=${roomId}`;
    link.download = `room_${roomId}_qr.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

roomPopupOpen.addEventListener("click", () => {
    debugLog("Opening register room popup");
    roomPopupContainer.style.display = "flex";
    document.body.classList.add("popup-active");
    roomPopupImagePreview.src = "../images/logos/studyroom.png";
    roomPopupImageInput.value = "";
});

roomPopupClose.addEventListener("click", () => {
    debugLog("Closing register room popup");
    roomPopupContainer.style.display = "none";
    document.body.classList.remove("popup-active");
    roomPopupImagePreview.src = "../images/logos/studyroom.png";
});

roomPopupUpload.addEventListener("click", () => {
    debugLog("Triggering file input for register room");
    roomPopupImageInput.click();
});

roomPopupImageInput.addEventListener("change", (event) => {
    try {
        const file = event.target.files[0];
        debugLog(`Image input changed, file: ${file ? file.name : 'none'}, type: ${file ? file.type : 'none'}, size: ${file ? file.size : 'none'}`);
        if (file && ["image/png", "image/jpeg"].includes(file.type)) {
            const reader = new FileReader();
            reader.onload = () => {
                debugLog("Image loaded for preview");
                roomPopupImagePreview.src = reader.result;
            };
            reader.readAsDataURL(file);
            document.getElementById("file_selected").value = "1";
        } else {
            debugLog("Invalid file type selected");
            alert("Please upload a PNG or JPEG image.");
            roomPopupImageInput.value = "";
            document.getElementById("file_selected").value = "";
        }
    } catch (error) {
        debugLog(`Error in image input change: ${error.message}`);
    }
});

roomPopupForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    debugLog("Starting room form submission");

    const formData = new FormData(roomPopupForm);
    const file = roomPopupImageInput.files[0];

    if (!file || !["image/png", "image/jpeg"].includes(file.type)) {
        alert("Please upload a valid PNG or JPEG room image.");
        return;
    }

    try {
        debugLog("Sending form data to server...");
        const response = await fetch("../php/addRoom.php", {
            method: "POST",
            body: formData,
            cache: 'no-cache'
        });

        const responseData = await response.json();
        debugLog(`Server response (${response.status}):`, responseData);

        if (response.ok && responseData.success) {
            alert(responseData.message);
            roomPopupContainer.style.display = "none";
            document.body.classList.remove("popup-active");
            roomPopupForm.reset();
            roomPopupImagePreview.src = "../images/logos/studyroom.png";
            
            // Force a complete page reload
            window.location.href = window.location.href.split('?')[0] + '?t=' + new Date().getTime();
        } else {
            throw new Error(responseData.message || 'Failed to add room');
        }
    } catch (error) {
        debugLog("Error in form submission: " + error.message);
        alert("Error: " + error.message);
    }
});

// Function to refresh the rooms list
async function refreshRoomsList() {
    debugLog("Refreshing rooms list");
    try {
        const response = await fetch(window.location.href, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        });
        
        if (!response.ok) throw new Error('Failed to refresh rooms');
        
        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');
        const newRoomsList = doc.querySelector('.rooms-list');
        
        if (newRoomsList) {
            const currentRoomsList = document.querySelector('.rooms-list');
            if (currentRoomsList) {
                currentRoomsList.innerHTML = newRoomsList.innerHTML;
                setupEditButtons();
                debugLog("Rooms list refreshed successfully");
            }
        }
    } catch (error) {
        debugLog("Error refreshing rooms: " + error.message);
        window.location.reload(true);
    }
}

// Update edit button setup
function setupEditButtons() {
    debugLog("Setting up edit button event listeners");
    document.querySelectorAll('.ts.room-edit-btn').forEach(button => {
        button.style.cursor = 'pointer';
        button.addEventListener("click", handleEditButtonClick);
    });
}

// Handle edit button click
function handleEditButtonClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    try {
        // Get the clicked element and find the closest edit button
        const clickedElement = e.target;
        const editButton = clickedElement.closest('.room-edit-btn');
        
        if (!editButton) {
            debugLog("Error: Could not find edit button");
            return;
        }

        // Get room_id from the button's data attribute
        const roomId = editButton.getAttribute('data-room-id');
        debugLog(`Edit button clicked for room_id: ${roomId}`);

        if (!roomId) {
            debugLog("Error: No room_id found on edit button");
            alert("Cannot edit: Room ID missing");
            return;
        }

        // Store the current room ID
        currentRoomId = roomId;

        // Get room data from the room holder
        const roomHolder = document.querySelector(`.room-holder[data-room-id="${roomId}"]`);
        if (!roomHolder) {
            debugLog(`Error: Could not find room holder for room_id: ${roomId}`);
            alert("Room data not found");
            return;
        }

        // Get all the room data
        const roomData = {
            room_id: roomId,
            room_name: roomHolder.querySelector('.room-name')?.textContent?.trim() || '',
            student_capacity: roomHolder.querySelector('.student-capacity')?.textContent?.trim() || '',
            chairs: roomHolder.querySelector('.chairs-count')?.textContent?.trim() || '',
            tables: roomHolder.querySelector('.tables-count')?.textContent?.trim() || '',
            status: roomHolder.querySelector('.room-status')?.textContent?.trim() || '',
            room_image: roomHolder.querySelector('.room-image')?.src || '',
            qr_code: roomHolder.querySelector('.qr-code-img')?.src || ''
        };

        debugLog('Room data collected:', JSON.stringify(roomData));

        // Show and populate the edit form
        showEditForm(roomData);

    } catch (error) {
        debugLog(`Error in handleEditButtonClick: ${error.message}`);
        console.error(error);
        alert("Error loading room data: " + error.message);
    }
}

// Function to show and populate the edit form
function showEditForm(roomData) {
    try {
        debugLog('Showing edit form for room:', JSON.stringify(roomData));

        // Show the edit overlay
        const overlay = document.getElementById('room-edit-overlay');
        if (!overlay) {
            throw new Error('Edit overlay not found');
        }
        overlay.style.display = "flex";
        document.body.classList.add("popup-active");

        // Set the room ID in the form
        const roomIdInput = document.getElementById('edit_room_id');
        if (!roomIdInput) {
            throw new Error('Room ID input not found');
        }
        roomIdInput.value = roomData.room_id;
        debugLog(`Set room_id in form to: ${roomData.room_id}`);

        // Set form field values with null checks
        const formFields = {
            'roomid_input': roomData.room_name,
            'student_id': roomData.student_capacity,
            'chair_id': roomData.chairs,
            'table_id': roomData.tables,
            'choices': roomData.status
        };

        for (const [fieldId, value] of Object.entries(formFields)) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = value;
                debugLog(`Set ${fieldId} to: ${value}`);
            } else {
                debugLog(`Warning: Field ${fieldId} not found`);
            }
        }

        // Set the room image
        const editRoomImage = document.getElementById('edit_room_image');
        if (editRoomImage && roomData.room_image) {
            editRoomImage.src = roomData.room_image;
            debugLog('Set room image');
        }

        // Set the QR code image
        const editQrCode = document.getElementById('edit_qr_code');
        if (editQrCode && roomData.qr_code) {
            editQrCode.src = roomData.qr_code;
            debugLog('Set QR code image');
        }

    } catch (error) {
        debugLog(`Error in showEditForm: ${error.message}`);
        console.error(error);
        alert("Error displaying edit form: " + error.message);
    }
}

// Handle room edit form submission
roomEditForm.addEventListener("submit", async (event) => {
    try {
        event.preventDefault();
        debugLog("Starting edit form submission");

        // Create a new FormData object from the form
        const formData = new FormData(roomEditForm);
        const roomId = editRoomId.value;
        const file = editRoomImageInput.files[0];

        debugLog(`Edit form submission - room_id: ${roomId}, has new image: ${!!file}`);
        debugLog(`Form data entries: ${[...formData.entries()].map(e => e[0]).join(', ')}`);

        if (!roomId) {
            debugLog("Error: Edit form submitted with empty room_id");
            alert("Cannot update: Room ID is missing.");
            return;
        }

        // Submit the form
        debugLog("Submitting edit form...");
        const response = await fetch("../php/updateRoom.php", {
            method: "POST",
            body: formData
        });

        const responseData = await response.json();
        debugLog("Server response:", responseData);

        if (responseData.success) {
            alert(responseData.message);
            roomEditOverlay.style.display = "none";
            document.body.classList.remove("popup-active");
            window.location.reload();
        } else {
            throw new Error(responseData.message || 'Unknown error occurred');
        }

    } catch (error) {
        debugLog(`Error in edit form submit: ${error.message}`);
        alert("Error updating room: " + error.message);
    }
});

// Update the edit room image input handler
editRoomImageInput.addEventListener("change", (event) => {
    try {
        const file = event.target.files[0];
        debugLog(`Edit image input changed, file: ${file ? file.name : 'none'}, type: ${file ? file.type : 'none'}, size: ${file ? file.size : 'none'}`);
        
        if (file) {
            if (["image/png", "image/jpeg"].includes(file.type)) {
                const reader = new FileReader();
                reader.onload = () => {
                    debugLog("Image loaded for edit preview");
                    editRoomImage.src = reader.result;
                };
                reader.readAsDataURL(file);
                debugLog("New image ready for upload");
            } else {
                debugLog("Invalid file type selected for edit");
                alert("Please upload a PNG or JPEG image.");
                editRoomImageInput.value = "";
                // Keep the existing image preview
                const roomHolder = document.querySelector(`.room-holder[data-room-id="${currentRoomId}"]`);
                if (roomHolder) {
                    const existingImage = roomHolder.querySelector('.room-image');
                    if (existingImage) {
                        editRoomImage.src = existingImage.src;
                    }
                }
            }
        }
    } catch (error) {
        debugLog(`Error in edit image change: ${error.message}`);
        alert("Error handling image: " + error.message);
    }
});

// Handle delete button click
document.getElementById('room-edit-delete').addEventListener('click', function(e) {
    e.preventDefault();
    const roomId = document.getElementById('edit_room_id').value;
    if (!roomId) {
        debugLog("Error: No room ID found for delete");
        alert("Cannot delete: Room ID missing.");
        return;
    }
    
    debugLog(`Opening delete confirmation for room ID: ${roomId}`);
    document.getElementById('delete_room_id').value = currentRoomId;
    const deleteOverlay = document.getElementById('pop_up_delete_confirmation_overlay');
    if (deleteOverlay) {
        deleteOverlay.style.display = "flex";
    }
});

// Handle delete confirmation
document.getElementById('delete_room_form').addEventListener('submit', function(e) {
    const roomId = document.getElementById('delete_room_id').value;
    if (!roomId) {
        e.preventDefault();
        debugLog("Error: No room_id found in delete form");
        alert("Cannot delete: Room ID missing.");
        return;
    }
    debugLog(`Submitting delete form for room_id: ${roomId}`);
});

// Handle delete cancellation
document.getElementById('pop_up_delete_cancel_button').addEventListener('click', function() {
    debugLog("Canceling delete operation");
    document.getElementById('pop_up_delete_confirmation_overlay').style.display = "none";
});

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    debugLog("DOM loaded, initializing room functionality");
    setupEditButtons();
    
    // Single event listener for the upload button
    const uploadBtn = document.querySelector('.room-edit-upload-btn');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', () => {
            debugLog("Upload button clicked for edit");
            editRoomImageInput.click();
        });
    }
});

roomEditClose.addEventListener("click", () => {
    debugLog("Closing edit room popup");
    roomEditOverlay.style.display = "none";
    document.body.classList.remove("popup-active");
    roomEditForm.reset();
    editRoomImage.src = "../images/logos/studyroom.png";
    editRoomImageInput.value = "";
    currentRoomId = null;
});

// Add debug logging for room data
window.addEventListener('DOMContentLoaded', (event) => {
    debugLog("DOM loaded, checking rooms data");
    if (window.roomsData) {
        debugLog(`Found ${window.roomsData.length} rooms in roomsData`);
        window.roomsData.forEach(room => {
            debugLog(`Room ID: ${room.room_id}, Name: ${room.room_name}`);
        });
    } else {
        debugLog("No roomsData found in window object");
    }
    setupEditButtons();
});