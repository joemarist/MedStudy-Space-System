

function titlechanges(num, element) {
    document.querySelectorAll(".sidebar ul li").forEach(li => li.classList.remove("active"));
    element.classList.add("active");

    
    if (num == 1) {
        document.getElementById("titlename").textContent = "Dashboard";

        document.getElementById("dashboard").style.display = "flex";
        document.getElementById("rooms").style.display = "none";
        document.getElementById("Booking").style.display = "none";
        document.getElementById("reports").style.display = "none";
    } else if (num == 2) {
        document.getElementById("titlename").textContent = "Room";

        document.getElementById("dashboard").style.display = "none";
        document.getElementById("rooms").style.display = "block";
        document.getElementById("Booking").style.display = "none";
        document.getElementById("reports").style.display = "none";
    } else if (num == 3) {
        document.getElementById("titlename").textContent = "Booking";

        document.getElementById("dashboard").style.display = "none";
        document.getElementById("rooms").style.display = "none";
        document.getElementById("Booking").style.display = "block";
        document.getElementById("reports").style.display = "none";
    } else {
        document.getElementById("titlename").textContent = "Reports";

        document.getElementById("dashboard").style.display = "none";
        document.getElementById("rooms").style.display = "none";
        document.getElementById("Booking").style.display = "none";
        document.getElementById("reports").style.display = "block";
    }
}








document.addEventListener("DOMContentLoaded", function () {
    const calendar = document.getElementById("calendar-days");
    const monthTitle = document.getElementById("month-title");
    const prevMonthBtn = document.getElementById("prev-month");
    const nextMonthBtn = document.getElementById("next-month");

    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();

    const reservedDays = {
        "2025-03-03": "partially-reserved",
        "2025-03-05": "reserved",
        "2025-03-06": "partially-reserved",
        "2025-03-10": "reserved",
        "2025-03-11": "partially-reserved",
        "2025-03-14": "partially-reserved",
    };

    function generateCalendar(month, year) {
        calendar.innerHTML = "";
        const firstDay = new Date(year, month, 1).getDay();
        const totalDays = new Date(year, month + 1, 0).getDate();

        monthTitle.textContent = new Date(year, month).toLocaleString("en-US", { month: "long", year: "numeric" });

        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement("div");
            calendar.appendChild(emptyCell);
        }

        for (let day = 1; day <= totalDays; day++) {
            const dateKey = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
            const dayElement = document.createElement("div");
            dayElement.textContent = day;

            if (reservedDays[dateKey]) {
                dayElement.classList.add(reservedDays[dateKey]);
            }

            calendar.appendChild(dayElement);
        }
    }

    prevMonthBtn.addEventListener("click", () => {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        generateCalendar(currentMonth, currentYear);
    });

    nextMonthBtn.addEventListener("click", () => {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        generateCalendar(currentMonth, currentYear);
    });

    generateCalendar(currentMonth, currentYear);
});