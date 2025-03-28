

function titlechanges(num, element) {
  

    
    if (num == 1) {
        document.getElementById("titlename").textContent = "Dashboard";
        window.location.href = "dashboard.html";
    

        document.getElementById("dashboard").style.display = "flex";
        document.getElementById("rooms").style.display = "none";
        document.getElementById("Booking").style.display = "none";
        document.getElementById("reports").style.display = "none";
    } else if (num == 2) {
        document.getElementById("titlename").textContent = "Room";
        window.location.href = "rooms.html";
      

        document.getElementById("dashboard").style.display = "none";
        document.getElementById("rooms").style.display = "block";
        document.getElementById("Booking").style.display = "none";
        document.getElementById("reports").style.display = "none";
    } else if (num == 3) {
        document.getElementById("titlename").textContent = "Booking";
        window.location.href = "booking.html";

        document.getElementById("dashboard").style.display = "none";
        document.getElementById("rooms").style.display = "none";
        document.getElementById("Booking").style.display = "block";
        document.getElementById("reports").style.display = "none";
    } else {
        document.getElementById("titlename").textContent = "Reports";
        window.location.href = "reports.html";

        document.getElementById("dashboard").style.display = "none";
        document.getElementById("rooms").style.display = "none";
        document.getElementById("Booking").style.display = "none";
        document.getElementById("reports").style.display = "block";
    }
}









 const calendar = document.getElementById("calendar-days");
        const monthTitle = document.getElementById("month-title");
        const prevMonthBtn = document.getElementById("prev-month");
        const nextMonthBtn = document.getElementById("next-month");
        const popup = document.getElementById("popup");
        const popupContent = document.getElementById("popup-content");
        
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