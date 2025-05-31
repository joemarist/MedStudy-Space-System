// Remove the static chart initialization
// const ctx = document.getElementById("report_chart").getContext("2d");
// const reportChart = new Chart(ctx, { ... });

// Remove the static event listener
// document.querySelector(".report_filter").addEventListener("change", function() {
//     alert("Filter changed to: " + this.value);
// });

document.addEventListener('DOMContentLoaded', function() {
    // Chart configuration
    let reportChart = null;

    // Fetch and render report data
    function fetchReportData(type = 'monthly', year = null, month = null, startDate = null, endDate = null) {
        // Clear previous chart and summary
        if (reportChart) {
            reportChart.destroy();
        }
        
        // Reset summary cards
        document.getElementById('total_reservations').textContent = '0';
        document.getElementById('cancelled_bookings').textContent = '0';
        document.getElementById('no_show_bookings').textContent = '0';

        // Prepare query parameters
        const params = new URLSearchParams({
            type: type
        });

        // Add additional parameters based on type
        if (type === 'weekly' || type === 'monthly') {
            params.append('year', year || new Date().getFullYear());
        }
        
        if (type === 'weekly') {
            params.append('month', month || new Date().getMonth() + 1);
        }

        if (startDate && endDate) {
            params.append('start_date', startDate);
            params.append('end_date', endDate);
        }

        // Fetch data
        fetch(`/MedStudy-Space-System/Admin/php/get_reservation_reports.php?${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                renderReportChart(result.data, type);
                updateSummaryCards(result.data);
            } else {
                console.error('Error fetching report data:', result.message);
                // Clear chart if no data
                const ctx = document.getElementById('report_chart').getContext('2d');
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Clear chart on error
            const ctx = document.getElementById('report_chart').getContext('2d');
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        });
    }

    // Update summary cards
    function updateSummaryCards(data) {
        const totalReservations = document.getElementById('total_reservations');
        const cancelledBookings = document.getElementById('cancelled_bookings');
        const noShowBookings = document.getElementById('no_show_bookings');

        // Aggregate data
        const total = data.reduce((sum, item) => sum + parseInt(item.total_reservations || 0), 0);
        const cancelled = data.reduce((sum, item) => sum + parseInt(item.cancelled_bookings || 0), 0);
        const noShows = data.reduce((sum, item) => sum + parseInt(item.no_show_bookings || 0), 0);

        totalReservations.textContent = total;
        cancelledBookings.textContent = cancelled;
        noShowBookings.textContent = noShows;
    }

    // Render chart based on data and type
    function renderReportChart(data, type) {
        const ctx = document.getElementById('report_chart').getContext('2d');
        
        // Prepare chart data
        const labels = [];
        const totalReservations = [];
        const cancelledBookings = [];
        const noShowBookings = [];

        // Process data based on type
        switch(type) {
            case 'weekly':
                // Ensure 4-5 weeks are represented, even if some are empty
                const weekLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
                labels.push(...weekLabels);
                
                // Map data to corresponding weeks
                data.forEach(item => {
                    const weekIndex = parseInt(item.booking_week) - 1;
                    if (weekIndex >= 0 && weekIndex < 5) {
                        totalReservations[weekIndex] = parseInt(item.total_reservations || 0);
                        cancelledBookings[weekIndex] = parseInt(item.cancelled_bookings || 0);
                        noShowBookings[weekIndex] = parseInt(item.no_show_bookings || 0);
                    }
                });

                // Fill undefined values with 0
                labels.forEach((_, index) => {
                    totalReservations[index] = totalReservations[index] || 0;
                    cancelledBookings[index] = cancelledBookings[index] || 0;
                    noShowBookings[index] = noShowBookings[index] || 0;
                });
                break;
            case 'monthly':
                const monthLabels = [
                    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                ];
                labels.push(...monthLabels);
                
                // Map data to corresponding months
                data.forEach(item => {
                    const monthIndex = parseInt(item.booking_month) - 1;
                    totalReservations[monthIndex] = parseInt(item.total_reservations || 0);
                    cancelledBookings[monthIndex] = parseInt(item.cancelled_bookings || 0);
                    noShowBookings[monthIndex] = parseInt(item.no_show_bookings || 0);
                });

                // Fill undefined values with 0
                labels.forEach((_, index) => {
                    totalReservations[index] = totalReservations[index] || 0;
                    cancelledBookings[index] = cancelledBookings[index] || 0;
                    noShowBookings[index] = noShowBookings[index] || 0;
                });
                break;
            case 'yearly':
                data.forEach(item => {
                    labels.push(item.booking_year);
                    totalReservations.push(parseInt(item.total_reservations || 0));
                    cancelledBookings.push(parseInt(item.cancelled_bookings || 0));
                    noShowBookings.push(parseInt(item.no_show_bookings || 0));
                });
                break;
            default:
                data.forEach(item => {
                    labels.push(item.booking_date);
                    totalReservations.push(parseInt(item.total_reservations || 0));
                    cancelledBookings.push(parseInt(item.cancelled_bookings || 0));
                    noShowBookings.push(parseInt(item.no_show_bookings || 0));
                });
        }

        // Create chart
        reportChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Reservations',
                        data: totalReservations,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Cancelled Bookings',
                        data: cancelledBookings,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'No Shows',
                        data: noShowBookings,
                        backgroundColor: 'rgba(255, 206, 86, 0.6)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Bookings'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: `Reservation Report (${type.charAt(0).toUpperCase() + type.slice(1)})`
                    }
                }
            }
        });
    }

    // Initial load
    fetchReportData();

    // Filter type change
    const reportTypeSelect = document.getElementById('report_type');
    const yearFilter = document.getElementById('year_filter');
    const monthFilter = document.getElementById('month_filter');
    const dateRangeFilter = document.getElementById('date_range_filter');

    reportTypeSelect.addEventListener('change', function() {
        // Show/hide filters based on selected type
        switch(this.value) {
            case 'weekly':
                yearFilter.style.display = 'block';
                monthFilter.style.display = 'block';
                dateRangeFilter.style.display = 'none';
                break;
            case 'monthly':
                yearFilter.style.display = 'block';
                monthFilter.style.display = 'none';
                dateRangeFilter.style.display = 'none';
                break;
            case 'yearly':
                yearFilter.style.display = 'block';
                monthFilter.style.display = 'none';
                dateRangeFilter.style.display = 'none';
                break;
            default:
                yearFilter.style.display = 'none';
                monthFilter.style.display = 'none';
                dateRangeFilter.style.display = 'block';
        }
    });

    // Apply filter button
    const applyFilterBtn = document.getElementById('apply_report_filter');
    applyFilterBtn.addEventListener('click', function() {
        const type = reportTypeSelect.value;
        const year = document.getElementById('report_year').value;
        const month = type === 'weekly' ? document.getElementById('report_month').value : null;
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        // Fetch data based on selected filters
        if (type === 'weekly' || type === 'monthly' || type === 'yearly') {
            fetchReportData(type, year, month);
        } else {
            // Custom date range
            fetchReportData(type, null, null, startDate, endDate);
        }
    });
});

function openRoomUsage() {
    let modal = document.getElementById("roomUsageModal");
    modal.style.display = "flex";  
    modal.style.justifyContent = "center"; 
    modal.style.alignItems = "center";
}

function closeRoomUsage() {
    document.getElementById("roomUsageModal").style.display = "none";
}

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
        maintainAspectRatio: false,  
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        size: 14
                    }
                }
            }
        }
    }
});

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

window.onclick = function (event) {
    let modal = document.getElementById("bookingtrends_modal");
    if (event.target === modal) {
        modal.style.display = "none";
    }
};

let ctx1 = document.getElementById("bookingtrends_chart").getContext("2d");

let bookingTrendsChart = new Chart(ctx1, {
    type: "bar",
    data: {
        labels: ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
        datasets: [
            {
                label: "Reservations",
                data: [30, 45, 50, 70, 90, 40, 25], 
                backgroundColor: "#007bff"
            },
            {
                label: "Non-Reservations",
                data: [10, 15, 12, 20, 25, 18, 10], 
                backgroundColor: "#ff6384"
            },
            {
                label: "Cancelled",
                data: [5, 7, 6, 8, 10, 4, 3], 
                backgroundColor: "#36a2eb"
            },
            {
                label: "Dropped",
                data: [3, 5, 4, 6, 7, 3, 2], 
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