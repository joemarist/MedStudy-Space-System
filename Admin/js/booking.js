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









let selectedBookingCard = null; // To store the selected card for deletion

function openBookingPopup(element, firstName, middleName, lastName, contact, email, studyRoom, id) {
    document.getElementById('popup_firstname').value = firstName;
    document.getElementById('popup_middlename').value = middleName;
    document.getElementById('popup_lastname').value = lastName;
    document.getElementById('popup_contact').value = contact;
    document.getElementById('popup_email').value = email;
    document.getElementById('popup_studyroom').innerText = studyRoom;
    document.getElementById('popup_id').innerText = id;

    selectedBookingCard = element.closest('.booking_popup_card'); // Store the card reference
    document.getElementById('booking_popup_container').style.display = 'flex';
}

function closeBookingPopup() {
    document.getElementById('booking_popup_container').style.display = 'none';
}

function showCancelPopup() {
    document.getElementById('cancel_popup').style.display = 'flex'; // Ensure it displays correctly
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