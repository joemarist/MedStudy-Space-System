<?php
// Ensure proper error reporting and JSON headers
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Database connection parameters
$host = "localhost";
$user = "root";
$pass = "";
$db = "medstudy";

// Function to safely handle JSON response
function sendJsonResponse($success, $data = null, $message = '') {
    $response = [
        'success' => $success
    ];
    
    if ($success) {
        $response['data'] = $data;
    } else {
        $response['message'] = $message;
    }
    
    echo json_encode($response);
    exit;
}

try {
    // Establish database connection
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get filter parameters
    $type = $_GET['type'] ?? 'monthly';  // weekly, monthly, yearly
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    // Prepare base query
    $query = "
        SELECT 
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status = 'cancelled by student' OR status = 'cancelled by admin' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show_bookings,
            YEAR(booking_date) as booking_year,
            MONTH(booking_date) as booking_month,
            FLOOR((DAYOFMONTH(booking_date) - 1) / 7) + 1 as booking_week,
            DATE(booking_date) as booking_date
        FROM booking
        WHERE 1=1
    ";

    // Add date filtering
    if ($start_date && $end_date) {
        $query .= " AND booking_date BETWEEN ? AND ?";
    } else {
        // Default filtering based on type
        switch ($type) {
            case 'weekly':
                $query .= " AND YEAR(booking_date) = ? AND MONTH(booking_date) = ?";
                break;
            case 'monthly':
                $query .= " AND YEAR(booking_date) = ?";
                break;
            case 'yearly':
                // No additional filtering needed
                break;
        }
    }

    // Group by based on type
    switch ($type) {
        case 'weekly':
            $query .= " GROUP BY booking_year, booking_month, booking_week";
            break;
        case 'monthly':
            $query .= " GROUP BY booking_year, booking_month";
            break;
        case 'yearly':
            $query .= " GROUP BY booking_year";
            break;
        default:
            $query .= " GROUP BY booking_date";
    }

    // Order results for consistent display
    $query .= " ORDER BY booking_year, booking_month, booking_week";

    // Prepare statement
    $stmt = $conn->prepare($query);

    // Bind parameters
    if ($start_date && $end_date) {
        $stmt->bind_param('ss', $start_date, $end_date);
    } else {
        switch ($type) {
            case 'weekly':
                $stmt->bind_param('ii', $year, $month);
                break;
            case 'monthly':
                $stmt->bind_param('i', $year);
                break;
        }
    }

    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare data array
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Send successful response
    sendJsonResponse(true, $data);

} catch (Exception $e) {
    // Send error response
    sendJsonResponse(false, null, $e->getMessage());
} finally {
    // Close database connection if it was opened
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?> 