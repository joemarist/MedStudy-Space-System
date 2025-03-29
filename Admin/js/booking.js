function openBookingPopup(firstname, middlename, lastname, contact, email, studyroom, id) {
    document.getElementById("popup_firstname").value = firstname;
    document.getElementById("popup_middlename").value = middlename;
    document.getElementById("popup_lastname").value = lastname;
    document.getElementById("popup_contact").value = contact;
    document.getElementById("popup_email").value = email;
    document.getElementById("popup_studyroom").textContent = studyroom;
    document.getElementById("popup_id").textContent = id;

    document.getElementById("booking_popup_container").style.display = "flex";
}

function closeBookingPopup() {
    document.getElementById("booking_popup_container").style.display = "none";
}








let selectedBookingCard = null; // Store selected booking card

function openBookingPopup(element, firstName, middleName, lastName, contact, email, studyRoom, id, date) {
    document.getElementById('popup_firstname').value = firstName;
    document.getElementById('popup_middlename').value = middleName;
    document.getElementById('popup_lastname').value = lastName;
    document.getElementById('popup_contact').value = contact;
    document.getElementById('popup_email').value = email;
    document.getElementById('popup_studyroom').innerText = studyRoom;
    document.getElementById('popup_id').innerText = id;
    document.getElementById('popup_date').value = date;

    selectedBookingCard = element; // Store the clicked booking card
    document.getElementById('booking_popup_container').style.display = 'flex';
}

function closeBookingPopup() {
    document.getElementById('booking_popup_container').style.display = 'none';
}

function showCancelPopup() {
    if (selectedBookingCard) {
        document.getElementById('cancel_popup').style.display = 'flex';
    }
}

function closeCancelPopup() {
    document.getElementById('cancel_popup').style.display = 'none';
}

function confirmCancel() {
    if (selectedBookingCard) {
        selectedBookingCard.remove(); // Remove the selected booking card
        selectedBookingCard = null; // Reset selection
    }
    closeBookingPopup();
    closeCancelPopup();
}