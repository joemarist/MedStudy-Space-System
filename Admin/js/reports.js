const ctx = document.getElementById("report_chart").getContext("2d");

const reportChart = new Chart(ctx, {
    type: "bar",
    data: {
        labels: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
        datasets: [
            { label: "Total Reservation", data: [80, 30, 5, 25, 40, 70, 85], backgroundColor: "#ff6699" },
            { label: "Cancel || No-Shows", data: [100, 100, 2, 30, 50, 60, 30], backgroundColor: "#3366cc" },
            { label: "Most Frequent Rooms", data: [10, 35, 80, 5, 55, 90, 10], backgroundColor: "#66cc66" }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Filter change event
document.querySelector(".report_filter").addEventListener("change", function() {
    alert("Filter changed to: " + this.value);
});













function openRoomUsage() {
    let modal = document.getElementById("roomUsageModal");
    modal.style.display = "flex";  // Ensures modal is visible
    modal.style.justifyContent = "center"; // Centers modal
    modal.style.alignItems = "center";
}

// Close Room Usage Modal
function closeRoomUsage() {
    document.getElementById("roomUsageModal").style.display = "none";
}

// Sample Chart Data for Room Usage
const roomCtx = document.getElementById("report_room_chart").getContext("2d");

const reportRoomChart = new Chart(roomCtx, {
    type: "pie",
    data: {
        labels: ["Morning", "Afternoon", "Evening", "Night"],
        datasets: [{
            data: [40, 30, 20, 10],
            backgroundColor: ["#3366cc", "#ff6699", "#66cc66", "#ffcc33"]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,  // Allow better scaling
        plugins: {
            legend: {
                position: 'bottom', // Move legend below
                labels: {
                    font: {
                        size: 14
                    }
                }
            }
        }
    }
});
// Close modal when clicking outside
window.onclick = function(event) {
    let modal = document.getElementById("roomUsageModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
};



function change(){
    let x = document.getElementById("tg").value

    if(x == "room1"){
        document.getElementById("title_room").innerHTML = "Study Room 1";
    }else if(x == "room2"){
        document.getElementById("title_room").innerHTML = "Study Room 2";
    }else if(x == "room3"){
        document.getElementById("title_room").innerHTML = "Study Room 3";
    }else{
        document.getElementById("title_room").innerHTML = "Study Room 4";
    }
}














document.getElementById("bookingtrends_openModal").addEventListener("click", function () {
    document.getElementById("bookingtrends_modal").style.display = "block";
});

document.querySelector(".bookingtrends_close").addEventListener("click", function () {
    document.getElementById("bookingtrends_modal").style.display = "none";
});

// Close modal if user clicks outside
window.onclick = function (event) {
    let modal = document.getElementById("bookingtrends_modal");
    if (event.target === modal) {
        modal.style.display = "none";
    }
};

// Chart.js functionality
let ctx1 = document.getElementById("bookingtrends_chart").getContext("2d");

let bookingTrendsChart = new Chart(ctx1, {
    type: "bar",
    data: {
        labels: ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
        datasets: [
            {
                label: "Reservations",
                data: [30, 45, 50, 70, 90, 40, 25], // Example reservation data per day
                backgroundColor: "#007bff"
            },
            {
                label: "Non-Reservations",
                data: [10, 15, 12, 20, 25, 18, 10], // Example non-reservation data
                backgroundColor: "#ff6384"
            },
            {
                label: "Cancelled",
                data: [5, 7, 6, 8, 10, 4, 3], // Example cancelled reservations data
                backgroundColor: "#36a2eb"
            },
            {
                label: "Dropped",
                data: [3, 5, 4, 6, 7, 3, 2], // Example dropped reservations data
                backgroundColor: "#ffce56"
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});