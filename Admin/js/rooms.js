document.addEventListener("DOMContentLoaded", function () {
    const modalOverlay = document.getElementById("room-edit-overlay");
    const openModalBtn = document.getElementById("room-edit-open");
    const closeModalBtn = document.getElementById("room-edit-close");
    const updateBtn = document.querySelector(".room-edit-btn-update");
    const statusSelect = document.getElementById("choices");
    const statusLabel = document.getElementById("status");

 
    const roomNameInput = document.getElementById("roomid_input");
    const studentInput = document.getElementById("student_id");
    const chairInput = document.getElementById("chair_id");
    const tableInput = document.getElementById("table_id");

   
    const roomNameLabel = document.getElementById("roomid");
    const studentLabel = document.getElementById("student_num");
    const chairLabel = document.getElementById("chair_num");
    const tableLabel = document.getElementById("table_num");

   
    const uploadButton = document.querySelector(".room-edit-upload-btn");
    const imagePreview = document.querySelector(".room-edit-image-container img");
    const bigPic = document.getElementById("bigpic"); 

    
    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.accept = "image/*"; 
    fileInput.style.display = "none";

  
    document.body.appendChild(fileInput);


    openModalBtn.addEventListener("click", function () {
        modalOverlay.classList.add("active");

       
        roomNameInput.value = roomNameLabel.innerText;
        studentInput.value = studentLabel.innerText;
        chairInput.value = chairLabel.innerText;
        tableInput.value = tableLabel.innerText;
    });

  
    closeModalBtn.addEventListener("click", function () {
        modalOverlay.classList.remove("active");
    });

    window.addEventListener("click", function (event) {
        if (event.target === modalOverlay) {
            modalOverlay.classList.remove("active");
        }
    });

    
    updateBtn.addEventListener("click", function () {
       
        roomNameLabel.innerText = roomNameInput.value;
        studentLabel.innerText = studentInput.value;
        chairLabel.innerText = chairInput.value;
        tableLabel.innerText = tableInput.value;

      
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

       
        modalOverlay.classList.remove("active");
    });

   
    uploadButton.addEventListener("click", function () {
        fileInput.click();
    });

    fileInput.addEventListener("change", function (event) {
        const file = event.target.files[0];

        if (file) {
            const reader = new FileReader();

            reader.onload = function (e) {
        
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

   
    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.accept = "image/*"; 
    fileInput.style.display = "none";
    document.body.appendChild(fileInput);

    
    openBtn.addEventListener("click", function () {
        popup.style.display = "flex";
    });

    
    closeBtn.addEventListener("click", function () {
        popup.style.display = "none";
    });

    
    window.addEventListener("click", function (event) {
        if (event.target === popup) {
            popup.style.display = "none";
        }
    });


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


    createBtn.addEventListener("click", function () {
        
        const newName = document.getElementById("room_popup_name").value;
        const newStudents = document.getElementById("room_popup_students").value;
        const newChairs = document.getElementById("room_popup_chairs").value;
        const newTables = document.getElementById("room_popup_tables").value;
        const newStatus = document.getElementById("room_popup_status").value;
        const newRoomKey = document.getElementById("room_popup_key").value;

     
        console.log("Room Created:");
        console.log("Name:", newName);
        console.log("Students:", newStudents);
        console.log("Chairs:", newChairs);
        console.log("Tables:", newTables);
        console.log("Status:", newStatus);
        console.log("Room Key:", newRoomKey);

    
        popup.style.display = "none";
    });
});








document.addEventListener("DOMContentLoaded", function () {
    const createBtn = document.getElementById("room_popup_create");
    const roomsContainer = document.getElementById("rooms");
    const imagePreview = document.getElementById("room_popup_image_preview");

    createBtn.addEventListener("click", function () {
    
        const roomName = document.getElementById("room_popup_name").value || "New Room";
        const studentNum = document.getElementById("room_popup_students").value || "0";
        const chairNum = document.getElementById("room_popup_chairs").value || "0";
        const tableNum = document.getElementById("room_popup_tables").value || "0";
        const status = document.getElementById("room_popup_status").value || "Available";
        const roomImageSrc = imagePreview.src;

     
        const newRoom = document.createElement("div");
        newRoom.classList.add("room-holder");

       
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
                <div class="ts" id="room-edit-open">
                    <img src="../images/logos/edit.png" width="30px">
                </div>
                <br><br><br><br><br><br><br><br><br><br>
                <div align="right">
                    <h3 id="status">${status}</h3>
                </div>
            </div>
        `

       
        roomsContainer.appendChild(newRoom);

   
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









let selectedRoom = null; 

document.addEventListener("DOMContentLoaded", function () {

    document.getElementById("pop_up_delete_confirmation_overlay").style.display = "none";

    
    document.querySelectorAll("#room-edit-open").forEach(button => {
        button.addEventListener("click", function () {
            selectedRoom = this.closest(".room-holder"); 
            document.getElementById("room-edit-overlay").style.display = "flex"; 
        });
    });

   
    document.querySelector(".room-edit-btn-delete").addEventListener("click", function () {
        document.getElementById("pop_up_delete_confirmation_overlay").style.display = "flex"; 
    });

   
    document.getElementById("pop_up_delete_confirm_button").addEventListener("click", function () {
        if (selectedRoom) {
            selectedRoom.remove(); 
            selectedRoom = null;
        }
        document.getElementById("pop_up_delete_confirmation_overlay").style.display = "none"; 
        document.getElementById("room-edit-overlay").style.display = "none"; 
    });

  
    document.getElementById("pop_up_delete_cancel_button").addEventListener("click", function () {
        document.getElementById("pop_up_delete_confirmation_overlay").style.display = "none"; 
    });


    document.getElementById("room-edit-close").addEventListener("click", function () {
        document.getElementById("room-edit-overlay").style.display = "none";
    });
}); 


























