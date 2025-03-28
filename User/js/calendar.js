document.addEventListener("DOMContentLoaded", function () {
    var calendarEl = document.getElementById("calendar");

    if (!calendarEl) {
        console.error("Error: Calendar element not found.");
        return;
    }

    var vacantDates = {
        "2025-03-24": { backgroundColor: "#006CFD", color: "white", borderRadius: "100px" }
    };

    var occupiedDates = {
        "2025-03-21": { backgroundColor: "#D4E9FF", color: "#006CFD", borderRadius: "100px" },
        "2025-03-20": { backgroundColor: "#D4E9FF", color: "#006CFD", borderRadius: "100px" },
        "2025-03-19": { backgroundColor: "#D4E9FF", color: "#006CFD", borderRadius: "100px" }
    };

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        height: "auto",
        contentHeight: "auto",
        expandRows: true,
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: ""
        },

        dayCellDidMount: function (info) {
            let dateObj = info.date;
            let dateStr = dateObj.getFullYear() + "-" + 
                          String(dateObj.getMonth() + 1).padStart(2, '0') + "-" + 
                          String(dateObj.getDate()).padStart(2, '0');

            console.log("Cell Date:", dateStr);

            if (vacantDates[dateStr]) {
                let style = vacantDates[dateStr];
                info.el.style.backgroundColor = style.backgroundColor;
                info.el.style.color = style.color;
                info.el.style.borderRadius = style.borderRadius;
                info.el.style.cursor = "pointer";
            } else if (occupiedDates[dateStr]) {
                let style = occupiedDates[dateStr];
                info.el.style.backgroundColor = style.backgroundColor;
                info.el.style.color = style.color;
                info.el.style.borderRadius = style.borderRadius;
                info.el.style.cursor = "pointer";
            } else{
                info.el.style.cursor = "pointer";
            }
        },

        dateClick: function (info) {
            let dateObj = info.date;
            let dateStr = dateObj.getFullYear() + "-" + 
                          String(dateObj.getMonth() + 1).padStart(2, '0') + "-" + 
                          String(dateObj.getDate()).padStart(2, '0');

            console.log("Clicked Date:", dateStr);

            if (vacantDates[dateStr]) {
                openBookOverlay();
            } else if (occupiedDates[dateStr]) {
                openFullyBookedOverlay();
            } else {
                openNotAvailableOverlay();
            }
        }
    });

    calendar.render();
});
