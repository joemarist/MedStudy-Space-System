function debugLog(message) {
    console.log('[dashboard.js] ' + message);
}

const actions = [
    "has entered the Study Room 1",
    "has entered the Study Room 2",
    "has booked a Study Room",
    "didn't show up",
    "left the Study Room",
    "is studying in the lounge",
    "has checked in at the library"
];

function titlechanges(num, element) {
    // Validate input
    if (typeof num !== 'number' || num < 1 || num > 4) {
        debugLog('Invalid navigation number');
        return;
    }

    // Remove active class from all sidebar items
    var sidebarItems = document.querySelectorAll('.sidebar ul li');
    for (var i = 0; i < sidebarItems.length; i++) {
        sidebarItems[i].classList.remove('active');
    }

    // Add active class to clicked item
    if (element) {
        element.classList.add('active');
    }

    // Navigation mapping
    var pages = {
        1: 'dashboard.html',
        2: 'rooms.php',
        3: 'booking.php',
        4: 'reports.php'
    };

    // Navigate to selected page
    var selectedPage = pages[num];
    if (selectedPage) {
        window.location.href = selectedPage;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    try {
        const calendar = document.getElementById("calendar-days");
        const monthTitle = document.getElementById("month-title");
        const prevMonthBtn = document.getElementById("prev-month");
        const nextMonthBtn = document.getElementById("next-month");
        const popup = document.getElementById("popup");
        const popupContent = document.getElementById("popup-content");

        if (!calendar || !monthTitle || !prevMonthBtn || !nextMonthBtn || !popup || !popupContent) {
            debugLog("Calendar elements not found, skipping calendar logic");
            return;
        }

        let currentDate = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();

        const reservedDays = {
            "2025-03-03": { status: "partially-reserved", reservations: ["Leonardo Clay Thompson"] },
            "2025-03-05": { status: "reserved", reservations: ["Jeniffer Claudia Perez"] },
            "2025-03-06": { status: "partially-reserved", reservations: ["Joshua Glen Garcia"] },
            "2025-03-10": { status: "reserved", reservations: ["Juan Dela Cruz"] },
            "2025-03-11": { status: "partially-reserved", reservations: ["Joshua Glen Garcia"] },
            "2025-03-14": { status: "partially-reserved", reservations: ["Leonardo Clay Thompson"] }
        };

        function generateCalendar(month, year) {
            calendar.innerHTML = "";
            const firstDay = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();
            monthTitle.textContent = new Date(year, month).toLocaleString("en-US", { month: "long", year: "numeric" });
            for (let i = 0; i < firstDay; i++) calendar.appendChild(document.createElement("div"));
            for (let day = 1; day <= totalDays; day++) {
                const dateKey = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                const dayElement = document.createElement("div");
                dayElement.textContent = day;
                if (reservedDays[dateKey]) dayElement.classList.add(reservedDays[dateKey].status);
                dayElement.addEventListener("click", () => showPopup(dateKey));
                calendar.appendChild(dayElement);
            }
        }

        function showPopup(date) {
            popup.style.display = "block";
            popupContent.innerHTML = "";
            if (reservedDays[date]) {
                const { status, reservations } = reservedDays[date];
                if (status === "reserved") {
                    popupContent.innerHTML = `<p><strong>Fully Booked!</strong></p>`;
                } else {
                    popupContent.innerHTML = `<p><strong>Reservations:</strong></p>`;
                    reservations.forEach(name => {
                        popupContent.innerHTML += `<div><img src='profile.png'> ${name}</div>`;
                    });
                }
            } else {
                popupContent.innerHTML = "<p>No Reservation has been made for this date.</p>";
            }
        }

        function closePopup() {
            popup.style.display = "none";
        }

        prevMonthBtn.addEventListener("click", () => { if (--currentMonth < 0) { currentMonth = 11; currentYear--; } generateCalendar(currentMonth, currentYear); });
        nextMonthBtn.addEventListener("click", () => { if (++currentMonth > 11) { currentMonth = 0; currentYear++; } generateCalendar(currentMonth, currentYear); });

        generateCalendar(currentMonth, currentYear);
    } catch (error) {
        debugLog(`Error in calendar logic: ${error.message}`);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    try {
        const activityList = document.getElementById("activity-list");
        const refreshButton = document.getElementById("refresh-activity");

        if (!activityList || !refreshButton) {
            debugLog("Activity elements not found, skipping activity logic");
            return;
        }

        const names = ["Christian Doong", "Vic Lawson", "Maria Brenen", "Lewis Anthony Godin", "Joshua Glen Garcia", "Emma Watson", "John Doe", "Jane Smith"];
        const actions = [
            "has entered the Study Room 1.",
            "has entered the Study Room 2.",
            "has booked a Study Room.",
            "didn't show up.",
            "left the Study Room.",
            "is studying in the lounge.",
            "has checked in at the library."
        ];

        let defaultActivities = [
            '<span class="icon">💡</span> Christian Doong has entered the Study Room 1.',
            '<span class="icon">💡</span> Vic Lawson didn\'t show up.',
            '<span class="icon">💡</span> Maria Brenen has booked a Study Room.',
            '<span class="icon">💡</span> Lewis Anthony Godin has entered the Study Room 2.',
            '<span class="icon">💡</span> Joshua Glen Garcia has booked a Study Room.'
        ];
        let latestActivities = [...defaultActivities];

        function getRandomItem(array) {
            return array[Math.floor(Math.random() * array.length)];
        }

        function addActivity() {
            const activityItem = document.createElement("div");
            activityItem.classList.add("activity-item");
            const activityText = `<span class="icon">💡</span> ${getRandomItem(names)} ${getRandomItem(actions)}`;
            activityItem.innerHTML = activityText;
            activityList.prepend(activityItem);
            latestActivities.unshift(activityText);
            if (latestActivities.length > 20) latestActivities.pop();
        }

        function refreshActivity() {
            activityList.innerHTML = "";
            defaultActivities.forEach(activityText => {
                const activityItem = document.createElement("div");
                activityItem.classList.add("activity-item");
                activityItem.innerHTML = activityText;
                activityList.appendChild(activityItem);
            });
            latestActivities = [...defaultActivities];
        }

        refreshButton.addEventListener("click", refreshActivity);
        setInterval(addActivity, 2000);
    } catch (error) {
        debugLog(`Error in activity logic: ${error.message}`);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    try {
        const studyRooms = document.getElementById("study-rooms");
        const bookings = document.getElementById("bookings");
        const occupiedRooms = document.getElementById("occupied-rooms");

        if (!studyRooms || !bookings || !occupiedRooms) {
            debugLog("Stats elements not found, skipping stats logic");
            return;
        }

        const originalValues = {
            studyRooms: parseInt(studyRooms.textContent, 10),
            bookings: parseInt(bookings.textContent, 10),
            occupiedRooms: parseInt(occupiedRooms.textContent, 10)
        };

        function updateStats() {
            let newStudyRooms = Math.floor(Math.random() * 50) + 1;
            let newBookings = Math.floor(Math.random() * 50) + 1;
            let newOccupiedRooms = Math.floor(Math.random() * 50) + 1;

            studyRooms.textContent = newStudyRooms;
            bookings.textContent = newBookings;
            occupiedRooms.textContent = newOccupiedRooms;

            if (newStudyRooms >= 50 || newBookings >= 50 || newOccupiedRooms >= 50) {
                setTimeout(() => {
                    studyRooms.textContent = originalValues.studyRooms;
                    bookings.textContent = originalValues.bookings;
                    occupiedRooms.textContent = originalValues.occupiedRooms;
                }, 1000);
            }
        }

        setInterval(updateStats, 5000);
    } catch (error) {
        debugLog(`Error in stats logic: ${error.message}`);
    }
});
