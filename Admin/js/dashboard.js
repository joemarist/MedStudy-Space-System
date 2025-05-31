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
        1: 'dashboard.php',
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

// Event Listeners for Dashboard Buttons
document.addEventListener('DOMContentLoaded', function() {
    debugLog('Initializing dashboard event listeners');

    // Sidebar Navigation
    const sidebarItems = document.querySelectorAll('.sidebar ul li');
    sidebarItems.forEach(item => {
        item.addEventListener('click', function() {
            const navNum = parseInt(this.getAttribute('data-nav'));
            titlechanges(navNum, this);
        });
    });

    // Profile Modal Interactions
    const modalTrigger = document.getElementById('modal-trigger');
    const profileModal = document.getElementById('profileModal');
    const closeModalBtn = profileModal?.querySelector('.close-btn');
    const logoutBtn = profileModal?.querySelector('.logout-btn');

    if (modalTrigger) {
        modalTrigger.addEventListener('click', function() {
            debugLog('Opening profile modal');
            profileModal.style.display = 'flex';
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            debugLog('Closing profile modal');
            profileModal.style.display = 'none';
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            debugLog('Logout initiated');
            const loadingPopup = document.getElementById('loadingPopup');
            if (loadingPopup) {
                loadingPopup.style.display = 'flex';
                setTimeout(() => {
                    window.location.href = 'loginAdmin.php';
                }, 3000);
            }
        });
    }

    // Real-Time Activity Buttons
    const refreshActivityBtn = document.getElementById('refresh-activity');
    const seeAllActivityBtn = document.getElementById('see-all-activity');

    if (refreshActivityBtn) {
        refreshActivityBtn.addEventListener('click', function() {
            debugLog('Refresh activity button clicked');
            fetch('/MedStudy-Space-System/Admin/php/clear_real_time_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Failed to clear activity');
                    });
                }
                return response.json();
            })
            .then(data => {
                const activityList = document.getElementById('activity-list');
                if (data.success) {
                    activityList.innerHTML = '<div class="activity-item" style="color: green;">Real-time activity cleared successfully.</div>';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    activityList.innerHTML = `<div class="activity-item" style="color: red;">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error clearing activity:', error);
                const activityList = document.getElementById('activity-list');
                activityList.innerHTML = `<div class="activity-item" style="color: red;">Error: ${error.message}</div>`;
            });
        });
    }

    if (seeAllActivityBtn) {
        seeAllActivityBtn.addEventListener('click', function() {
            debugLog('See all activity button clicked');
            
            // Show loading indicator
            const mainContainer = document.querySelector('.main-container');
            mainContainer.innerHTML = `
                <div class="loading-container" style="
                    display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    height: 100%; 
                    background-color: #f4f4f4;
                ">
                    <div class="spinner" style="
                        width: 50px; 
                        height: 50px; 
                        border: 5px solid #007bff; 
                        border-top: 5px solid transparent; 
                        border-radius: 50%; 
                        animation: spin 1s linear infinite;
                    "></div>
                </div>
            `;

            // Add spinning animation keyframes
            const styleEl = document.createElement('style');
            styleEl.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(styleEl);

            fetch('/MedStudy-Space-System/Admin/php/get_all_notifications.php?per_page=6')
            .then(response => {
                // Check if response is OK and is JSON
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new TypeError("Response is not JSON");
                }
                
                return response.json();
            })
            .then(data => {
                // Validate data structure
                if (!data || typeof data !== 'object') {
                    throw new Error('Invalid response format');
                }

                if (data.success && Array.isArray(data.notifications)) {
                    // Create full-screen notifications view
                    const notificationsContainer = document.createElement('div');
                    notificationsContainer.className = 'full-notifications-container';
                    notificationsContainer.innerHTML = `
                        <div class="notifications-header">
                            <h1>All Notifications</h1>
                            <button id="back-to-dashboard" class="back-btn">← Back to Dashboard</button>
                        </div>
                        
                        <div class="notifications-filter">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="type-filter">Type:</label>
                                    <select id="type-filter">
                                        <option value="">All Types</option>
                                        <option value="booking">Booking</option>
                                        <option value="checkin">Check-in</option>
                                        <option value="checkout">Check-out</option>
                                        <option value="room_status">Room Status</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label for="date-from">From:</label>
                                    <input type="date" id="date-from">
                                </div>
                                
                                <div class="filter-group">
                                    <label for="date-to">To:</label>
                                    <input type="date" id="date-to">
                                </div>
                                
                                <div class="filter-group">
                                    <label for="search-filter">Search:</label>
                                    <input type="text" id="search-filter" placeholder="Search notifications">
                                </div>
                                
                                <div class="filter-group">
                                    <button id="apply-filters">Apply Filters</button>
                                    <button id="reset-filters">Reset</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="notifications-pagination">
                            <button id="prev-page" disabled>Previous</button>
                            <span id="page-info">Page 1 of 1</span>
                            <button id="next-page" disabled>Next</button>
                        </div>
                        
                        <div class="notifications-grid" id="notifications-grid">
                            ${data.notifications.map(notification => `
                                <div class="notification-card" data-notification-id="${notification.id}">
                                    <div class="notification-header">
                                        <img src="${notification.user.profile_pic}" alt="User Profile" class="user-profile-pic">
                                        <div class="user-info">
                                            <h3>${notification.user.name}</h3>
                                            <p class="user-email">${notification.user.email}</p>
                                        </div>
                                    </div>
                                    <div class="notification-body">
                                        <span class="notification-type ${notification.type}">${notification.type}</span>
                                        <p class="notification-message">${notification.message}</p>
                                        ${notification.details ? `<p class="notification-details">${notification.details}</p>` : ''}
                                    </div>
                                    <div class="notification-footer">
                                        <span class="notification-timestamp">${notification.created_at}</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;

                    // Replace main container content
                    mainContainer.innerHTML = '';
                    mainContainer.appendChild(notificationsContainer);

                    // Add back button event listener
                    const backButton = document.getElementById('back-to-dashboard');
                    backButton.addEventListener('click', () => {
                        location.reload(); // Reload to go back to dashboard
                    });

                    // Pagination and Filtering Logic
                    let currentPage = data.pagination.page;
                    const totalPages = data.pagination.total_pages;
                    const totalCount = data.pagination.total_count;

                    // Pagination buttons
                    const prevPageBtn = document.getElementById('prev-page');
                    const nextPageBtn = document.getElementById('next-page');
                    const pageInfo = document.getElementById('page-info');

                    // Update pagination buttons and page info
                    function updatePaginationControls() {
                        prevPageBtn.disabled = currentPage <= 1;
                        nextPageBtn.disabled = currentPage >= totalPages;
                        pageInfo.textContent = `Page ${currentPage} of ${totalPages} (${totalCount} total)`;
                    }
                    updatePaginationControls();

                    // Fetch notifications with current filters
                    function fetchNotifications(page = 1, filters = {}) {
                        const queryParams = new URLSearchParams({
                            page: page,
                            per_page: 6,
                            type: filters.type || '',
                            date_from: filters.dateFrom || '',
                            date_to: filters.dateTo || '',
                            search: filters.search || ''
                        });

                        // Show loading
                        const notificationsGrid = document.getElementById('notifications-grid');
                        notificationsGrid.innerHTML = `
                            <div class="loading-container" style="
                                display: flex; 
                                justify-content: center; 
                                align-items: center; 
                                width: 100%; 
                                padding: 20px;
                            ">
                                <div class="spinner" style="
                                    width: 50px; 
                                    height: 50px; 
                                    border: 5px solid #007bff; 
                                    border-top: 5px solid transparent; 
                                    border-radius: 50%; 
                                    animation: spin 1s linear infinite;
                                "></div>
                            </div>
                        `;

                        fetch(`/MedStudy-Space-System/Admin/php/get_all_notifications.php?${queryParams}`)
                        .then(response => {
                            // Detailed error handling
                            if (!response.ok) {
                                // Try to parse error response
                                return response.text().then(errorText => {
                                    console.error('Server Error Response:', errorText);
                                    throw new Error(`HTTP error! status: ${response.status}, response: ${errorText}`);
                                });
                            }
                            
                            // Check content type
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                return response.text().then(text => {
                                    console.error('Non-JSON Response:', text);
                                    throw new TypeError(`Expected JSON, got ${contentType}: ${text}`);
                                });
                            }
                            
                            return response.json();
                        })
                        .then(data => {
                            // Validate response structure
                            if (!data || typeof data !== 'object') {
                                throw new Error('Invalid response format');
                            }

                            if (!data.success) {
                                throw new Error(data.message || 'Unknown error occurred');
                            }

                            if (!Array.isArray(data.notifications)) {
                                throw new Error('Notifications is not an array');
                            }

                            // Update notifications grid
                            const notificationsGrid = document.getElementById('notifications-grid');
                            notificationsGrid.innerHTML = data.notifications.length > 0 
                                ? data.notifications.map(notification => `
                                    <div class="notification-card" data-notification-id="${notification.id}">
                                        <div class="notification-header">
                                            <img src="${notification.user.profile_pic}" alt="User Profile" class="user-profile-pic">
                                            <div class="user-info">
                                                <h3>${notification.user.name}</h3>
                                                <p class="user-email">${notification.user.email}</p>
                                            </div>
                                        </div>
                                        <div class="notification-body">
                                            <span class="notification-type ${notification.type}">${notification.type}</span>
                                            <p class="notification-message">${notification.message}</p>
                                            ${notification.details ? `<p class="notification-details">${notification.details}</p>` : ''}
                                        </div>
                                        <div class="notification-footer">
                                            <span class="notification-timestamp">${notification.created_at}</span>
                                        </div>
                                    </div>
                                `).join('')
                                : `<div class="no-notifications">No notifications found.</div>`;

                            // Update pagination
                            currentPage = data.pagination.page;
                            updatePaginationControls();
                        })
                        .catch(error => {
                            console.error('Error fetching notifications:', error);
                            const notificationsGrid = document.getElementById('notifications-grid');
                            notificationsGrid.innerHTML = `
                                <div class="error-container">
                                    <h3>Error Fetching Notifications</h3>
                                    <p>${error.message}</p>
                                    <button id="retry-fetch" class="retry-btn">Retry</button>
                                </div>
                            `;

                            // Add retry button event listener
                            const retryBtn = document.getElementById('retry-fetch');
                            if (retryBtn) {
                                retryBtn.addEventListener('click', () => {
                                    fetchNotifications(page, filters);
                                });
                            }
                        });
                    }

                    // Apply Filters Event Listener
                    const applyFiltersBtn = document.getElementById('apply-filters');
                    const resetFiltersBtn = document.getElementById('reset-filters');
                    const typeFilter = document.getElementById('type-filter');
                    const dateFromFilter = document.getElementById('date-from');
                    const dateToFilter = document.getElementById('date-to');
                    const searchFilter = document.getElementById('search-filter');

                    applyFiltersBtn.addEventListener('click', () => {
                        const filters = {
                            type: typeFilter.value,
                            dateFrom: dateFromFilter.value,
                            dateTo: dateToFilter.value,
                            search: searchFilter.value
                        };
                        fetchNotifications(1, filters);
                    });

                    // Reset Filters Event Listener
                    resetFiltersBtn.addEventListener('click', () => {
                        typeFilter.value = '';
                        dateFromFilter.value = '';
                        dateToFilter.value = '';
                        searchFilter.value = '';
                        fetchNotifications(1);
                    });

                    // Pagination Event Listeners
                    prevPageBtn.addEventListener('click', () => {
                        if (currentPage > 1) {
                            const filters = {
                                type: typeFilter.value,
                                dateFrom: dateFromFilter.value,
                                dateTo: dateToFilter.value,
                                search: searchFilter.value
                            };
                            fetchNotifications(currentPage - 1, filters);
                        }
                    });

                    nextPageBtn.addEventListener('click', () => {
                        if (currentPage < totalPages) {
                            const filters = {
                                type: typeFilter.value,
                                dateFrom: dateFromFilter.value,
                                dateTo: dateToFilter.value,
                                search: searchFilter.value
                            };
                            fetchNotifications(currentPage + 1, filters);
                        }
                    });
                } else {
                    // Handle case where success is false or notifications is not an array
                    throw new Error(data.message || 'Failed to fetch notifications');
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                
                // Show detailed error in main container
                mainContainer.innerHTML = `
                    <div class="error-container">
                        <h2>Error Fetching Notifications</h2>
                        <p>${error.message}</p>
                        <button id="back-to-dashboard" class="back-btn">← Back to Dashboard</button>
                    </div>
                `;

                // Add back button event listener
                const backButton = document.getElementById('back-to-dashboard');
                backButton.addEventListener('click', () => {
                    location.reload(); // Reload to go back to dashboard
                });
            });
        });
    }
});
