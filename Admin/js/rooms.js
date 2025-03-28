const modalOverlay = document.getElementById("room-edit-overlay");
const openModalBtn = document.getElementById("room-edit-open");
const closeModalBtn = document.getElementById("room-edit-close");

openModalBtn.addEventListener("click", function () {
    modalOverlay.classList.add("active");
});

closeModalBtn.addEventListener("click", function () {
    modalOverlay.classList.remove("active");
});

window.addEventListener("click", function (event) {
    if (event.target === modalOverlay) {
        modalOverlay.classList.remove("active");
    }
});