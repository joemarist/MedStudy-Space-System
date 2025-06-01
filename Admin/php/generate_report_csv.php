<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=MedStudy_Reservation_Report.csv');

// Direct database connection function
function getDatabaseConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "medstudy";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Get report parameters
$type = $_GET['type'] ?? 'monthly';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? null;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Prepare the query based on report type
try {
    $conn = getDatabaseConnection();
    
    // Prepare base query
    $query = "
        SELECT 
            CASE 
                WHEN '$type' = 'weekly' THEN CONCAT('Week ', WEEK(created_at, 1) + 1)
                WHEN '$type' = 'monthly' THEN DATE_FORMAT(created_at, '%b')
                WHEN '$type' = 'yearly' THEN YEAR(created_at)
                ELSE DATE(created_at)
            END AS period,
            COUNT(*) AS total_reservations,
            SUM(CASE WHEN status = 'cancelled by student' OR status = 'cancelled by admin' THEN 1 ELSE 0 END) AS cancelled_bookings,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) AS no_show_bookings,
            ROUND(AVG(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100, 2) AS completion_rate
        FROM booking
        WHERE 1=1
    ";

    // Add filters based on report type
    switch ($type) {
        case 'weekly':
            $query .= " AND YEAR(created_at) = '$year' AND MONTH(created_at) = '$month'";
            $query .= " GROUP BY WEEK(created_at, 1)";
            break;
        case 'monthly':
            $query .= " AND YEAR(created_at) = '$year'";
            $query .= " GROUP BY MONTH(created_at)";
            break;
        case 'yearly':
            $query .= " GROUP BY YEAR(created_at)";
            break;
        default:
            // Custom date range
            if ($start_date && $end_date) {
                $query .= " AND created_at BETWEEN '$start_date' AND '$end_date'";
            }
            $query .= " GROUP BY DATE(created_at)";
    }

    // Debug: log the query
    error_log("Generated Query: " . $query);

    $result = $conn->query($query);

    // Check for query errors
    if ($result === false) {
        throw new Exception("Query error: " . $conn->error);
    }

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write CSV headers
    fputcsv($output, [
        'Report Type: ' . ucfirst($type),
        'Generated on: ' . date('Y-m-d H:i:s'),
        'Year: ' . $year
    ]);
    fputcsv($output, []); // Empty line for spacing

    // Write column headers
    fputcsv($output, [
        'Period',
        'Total Reservations',
        'Cancelled Bookings',
        'No Show Bookings',
        'Completion Rate (%)'
    ]);

    // Write data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['period'],
            $row['total_reservations'],
            $row['cancelled_bookings'],
            $row['no_show_bookings'],
            $row['completion_rate']
        ]);
    }

    // Write summary
    fputcsv($output, []); // Empty line for spacing
    fputcsv($output, ['Summary Report']);
    
    // Calculate total summary
    $total_query = preg_replace('/GROUP BY\s+\w+\(\w+,?\s*\d*\)/', 'HAVING 1=1', $query);
    error_log("Total Summary Query: " . $total_query);
    
    $total_result = $conn->query($total_query);
    
    // Check for total query errors
    if ($total_result === false) {
        throw new Exception("Total summary query error: " . $conn->error);
    }
    
    $total_row = $total_result->fetch_assoc();

    fputcsv($output, [
        'Total Reservations',
        $total_row['total_reservations']
    ]);
    fputcsv($output, [
        'Total Cancelled Bookings',
        $total_row['cancelled_bookings']
    ]);
    fputcsv($output, [
        'Total No Show Bookings',
        $total_row['no_show_bookings']
    ]);
    fputcsv($output, [
        'Overall Completion Rate',
        $total_row['completion_rate'] . '%'
    ]);

    fclose($output);
    exit();
} catch (Exception $e) {
    // Error handling
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=error_report.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Error Generating Report']);
    fputcsv($output, [$e->getMessage()]);
    fclose($output);
    
    // Log the full error for debugging
    error_log("Report Generation Error: " . $e->getMessage());
    exit();
} 