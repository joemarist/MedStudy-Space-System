document.addEventListener("DOMContentLoaded", function () {
    var calendarEl = document.getElementById("calendar");

    if (!calendarEl) {
        console.error("Error: Calendar element not found.");
        return;
    }

    // Global variable to store last fetched bookings with more robust tracking
    window.bookingCache = {
        roomId: null,
        startDate: null,
        endDate: null,
        bookings: []
    };

    // Enhanced booking fetch function with persistent marking
    function fetchBookings(info, successCallback, roomId = 1, maxRetries = 3) {
        // Validate input parameters
        if (!info || !info.startStr || !info.endStr) {
            console.error('Invalid fetch parameters', info);
            return;
        }

        function attemptFetch(retriesLeft) {
            // Ensure all required parameters are present
            const fetchData = {
                room_id: roomId,
                start_date: info.startStr,
                end_date: info.endStr
            };

            // Validate fetchData
            const missingFields = Object.entries(fetchData)
                .filter(([key, value]) => value === undefined || value === null)
                .map(([key]) => key);

            if (missingFields.length > 0) {
                console.error('Missing required fields:', missingFields);
                return;
            }

            fetch('/MedStudy-Space-System/User/php/get_bookings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(fetchData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update global booking cache
                    window.bookingCache = {
                        roomId: roomId,
                        startDate: info.startStr,
                        endDate: info.endStr,
                        bookings: data.bookings
                    };
                    successCallback(data.bookings);
                } else {
                    throw new Error(data.message || 'Failed to fetch bookings');
                }
            })
            .catch(error => {
                console.error('Booking fetch error:', error);
                
                // Retry mechanism
                if (retriesLeft > 0) {
                    console.log(`Retrying booking fetch... (${retriesLeft} attempts left)`);
                    setTimeout(() => attemptFetch(retriesLeft - 1), 500);
                } else {
                    console.error('All booking fetch attempts failed');
                    // Use cached bookings if available
                    if (window.bookingCache.bookings.length > 0) {
                        successCallback(window.bookingCache.bookings);
                    }
                }
            });
        }

        // Start the fetch with max retries
        attemptFetch(maxRetries);
    }

    // Persistent marking function with enhanced hover effects
    function persistMarkings(calendar, bookings) {
        // Ensure bookings is an array
        if (!Array.isArray(bookings)) {
            console.error('Invalid bookings data:', bookings);
            return;
        }

        // Create a map of bookings by date for easier lookup
        const bookingsByDate = {};
        bookings.forEach(booking => {
            if (booking && booking.date) {
                bookingsByDate[booking.date] = booking;
            }
        });

        // Select all calendar day elements
        const dateEls = document.querySelectorAll('.fc-daygrid-day');
        dateEls.forEach(el => {
            const dateNumberEl = el.querySelector('.fc-daygrid-day-number');
            const date = el.getAttribute('data-date');
            
            // Reset styles
            if (dateNumberEl) {
                dateNumberEl.style.color = '';
                dateNumberEl.style.fontWeight = '';
                dateNumberEl.style.transform = '';
                
                // Reset classes
                dateNumberEl.classList.remove('booking-date-number', 'user-booking', 'cancelled-booking');
                
                // Add hover effect
                dateNumberEl.classList.add('booking-date-number');
            }
            
            // Remove existing classes
            el.classList.remove(
                'fc-day-user-booking', 
                'fc-day-cancelled', 
                'fc-day-past'
            );

            // Check if this date has a booking
            const booking = bookingsByDate[date];
            if (booking) {
                // Determine marking based on status
                switch(booking.status) {
                    case 'cancelled':
                        el.classList.add('fc-day-cancelled');
                        if (dateNumberEl) {
                            dateNumberEl.style.color = '#FF4D4D';
                            dateNumberEl.style.fontWeight = 'bold';
                            dateNumberEl.classList.add('cancelled-booking');
                        }
                        break;
                    case 'user-booking':
                        el.classList.add('fc-day-user-booking');
                        if (dateNumberEl) {
                            dateNumberEl.style.color = '#014E6F';
                            dateNumberEl.style.fontWeight = 'bold';
                            dateNumberEl.style.transform = 'scale(1.1)';
                            dateNumberEl.classList.add('user-booking');
                        }
                        break;
                }
            }
        });
    }

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

        // Ensure markings persist on date range changes
        datesSet: function(info) {
            // Use the current room's ID if available
            const roomId = window.currentRoom ? window.currentRoom.id : 1;
            
            fetchBookings(info, function(bookings) {
                // Persistent marking
                persistMarkings(calendar, bookings);
            }, roomId);
        },

        // Add viewDidMount to ensure markings persist
        viewDidMount: function(info) {
            const roomId = window.currentRoom ? window.currentRoom.id : 1;
            
            fetchBookings(info, function(bookings) {
                // Persistent marking
                persistMarkings(calendar, bookings);
            }, roomId);
        },

        dateClick: function (info) {
            // Existing dateClick logic
            var dateStr = info.dateStr;
            
            // Use the current room's ID if available
            const roomId = window.currentRoom ? window.currentRoom.id : 1;
            
            // Check if the date is a past date or weekend
            const clickedDate = new Date(dateStr);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const day = clickedDate.getDay();

            // Check if the date is a past date or weekend
            if (clickedDate < today || day === 0 || day === 6) {
                openNotAvailableOverlay();
                return;
            }
            
            // Time validation function
            function isValidBookingTime(startTime, endTime) {
                // Working hours: 8am to 5pm
                const startHour = startTime.getHours();
                const endHour = endTime.getHours();
                
                // Calculate duration in hours
                const durationHours = (endTime - startTime) / (1000 * 60 * 60);
                
                // Check time range and duration
                return (
                    startHour >= 8 && 
                    endHour <= 17 && 
                    durationHours >= (10/60) && 
                    durationHours <= 2
                );
            }

            // Modify the time selection to enforce working hours
            const timepicker = flatpickr("#startTime", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                defaultHour: 8,
                defaultMinute: 0,
                minTime: "08:00",
                maxTime: "16:50",
                onChange: function(selectedDates, dateStr, instance) {
                    // Update end time picker
                    const endTimePicker = document.getElementById("endTime")._flatpickr;
                    
                    // Set minimum end time to start time + 10 minutes
                    const startTime = new Date();
                    startTime.setHours(selectedDates[0].getHours(), selectedDates[0].getMinutes());
                    
                    const minEndTime = new Date(startTime);
                    minEndTime.setMinutes(startTime.getMinutes() + 10);
                    
                    endTimePicker.set('minTime', dateStr);
                    endTimePicker.set('defaultHour', minEndTime.getHours());
                    endTimePicker.set('defaultMinute', minEndTime.getMinutes());
                }
            });

            const endTimePicker = flatpickr("#endTime", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                minTime: "08:10",
                maxTime: "17:00",
                onChange: function(selectedDates, dateStr, instance) {
                    const startTime = new Date();
                    const endTime = new Date();
                    
                    // Get start and end times from pickers
                    const startTimePicker = document.getElementById("startTime")._flatpickr;
                    const startTimeStr = startTimePicker.selectedDateElem 
                        ? startTimePicker.formatDate(startTimePicker.selectedDates[0], "H:i") 
                        : "08:00";
                    
                    startTime.setHours(
                        parseInt(startTimeStr.split(':')[0]), 
                        parseInt(startTimeStr.split(':')[1])
                    );
                    
                    endTime.setHours(
                        selectedDates[0].getHours(), 
                        selectedDates[0].getMinutes()
                    );
                    
                    // Validate booking time
                    if (!isValidBookingTime(startTime, endTime)) {
                        alert('Booking must be between 8:00 AM and 5:00 PM, and 10 minutes to 2 hours long');
                        instance.clear();
                        return;
                    }
                }
            });
            
            // Check booking status before opening overlay
            fetch('/MedStudy-Space-System/User/php/get_bookings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_id: roomId,
                    start_date: dateStr,
                    end_date: dateStr
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.bookings.length > 0) {
                    var booking = data.bookings[0];
                    
                    // Determine action based on booking status
                    switch(booking.status) {
                        case 'cancelled':
                            // Open a specific overlay for cancelled dates
                            openCancelledDateOverlay();
                            break;
                        case 'user-booking':
                            // Open duplicate booking overlay
                            openDuplicateBookingOverlay();
                            break;
                        default:
                            openBookOverlay(dateStr);
                    }
                } else {
                    openBookOverlay(dateStr);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                openNotAvailableOverlay();
            });
        }
    });

    calendar.render();

    // Add custom CSS for hover effects
    const styleEl = document.createElement('style');
    styleEl.textContent = `
        .booking-date-number {
            transition: all 0.3s ease;
            padding: 4px;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            z-index: 10;
        }

        .booking-date-number:hover {
            background-color: rgba(1, 78, 111, 0.1);
            transform: scale(1.2) !important;
            cursor: pointer;
        }

        .booking-date-number.user-booking:hover {
            background-color: rgba(1, 78, 111, 0.2);
        }

        .booking-date-number.cancelled-booking:hover {
            background-color: rgba(255, 77, 77, 0.2);
        }

        .fc-day-past .booking-date-number:hover {
            background-color: rgba(173, 181, 189, 0.1);
            cursor: not-allowed;
        }
    `;
    document.head.appendChild(styleEl);

    // Function to open cancelled date overlay
    function openCancelledDateOverlay() {
        const overlay = document.getElementById("cancelledDateOverlay");
        if (overlay) {
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
            }, 10);
            document.body.style.overflow = "hidden";
        }
    }

    // Function to close cancelled date overlay
    function closeCancelledDateOverlay() {
        const overlay = document.getElementById("cancelledDateOverlay");
        if (overlay) {
            overlay.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
            }, 300);
            document.body.style.overflow = "auto";
        }
    }
});
