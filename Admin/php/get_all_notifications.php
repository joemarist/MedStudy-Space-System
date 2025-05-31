<?php
// Disable all error output and set strict error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set headers to prevent any additional output
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Capture any output before JSON response
ob_start();

// Function to handle fatal errors
function handleFatalError() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // Log the error
        error_log('[Fatal Error in Notifications] ' . print_r($error, true), 3, '/tmp/notifications_fatal_error.log');
        
        // Clear any previous output
        ob_clean();
        
        // Send JSON error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'A fatal error occurred: ' . $error['message']
        ]);
        exit;
    }
}
register_shutdown_function('handleFatalError');

// Logging function
function logError($message) {
    error_log('[Notifications Error] ' . $message, 3, '/tmp/notifications_error.log');
}

// Function to safely handle JSON response
function sendJsonResponse($success, $data = null, $message = '') {
    // Clear any previous output
    ob_clean();
    
    $response = [
        'success' => $success
    ];
    
    if ($success) {
        $response['notifications'] = $data;
    } else {
        $response['message'] = $message;
    }
    
    echo json_encode($response);
    exit;
}

try {
    // Validate and sanitize input parameters
    $type_filter = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) ?: null;
    $date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_STRING) ?: null;
    $date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_STRING) ?: null;
    $search_query = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?: null;
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $per_page = filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT) ?: 6;
    
    // Ensure positive values
    $page = max(1, $page);
    $per_page = max(6, $per_page);
    $offset = ($page - 1) * $per_page;

    // Database connection parameters
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "medstudy";

    // Establish database connection
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Base query with dynamic WHERE clause
    $query_parts = [
        "SELECT 
            un.notification_id,
            un.type,
            un.message,
            un.additional_details,
            un.created_at,
            un.user_id,
            ua.email,
            ud.first_name,
            ud.last_name,
            ud.profile_pic"
    ];

    $where_clauses = [];
    $params = [];
    $param_types = '';

    // Type filter
    if ($type_filter) {
        $where_clauses[] = "un.type = ?";
        $params[] = $type_filter;
        $param_types .= 's';
    }

    // Date range filter
    if ($date_from && $date_to) {
        $where_clauses[] = "un.created_at BETWEEN ? AND ?";
        $params[] = $date_from . ' 00:00:00';
        $params[] = $date_to . ' 23:59:59';
        $param_types .= 'ss';
    }

    // Search filter (across multiple fields)
    if ($search_query) {
        $where_clauses[] = "(
            un.message LIKE ? OR 
            un.additional_details LIKE ? OR 
            ud.first_name LIKE ? OR 
            ud.last_name LIKE ? OR 
            ua.email LIKE ?
        )";
        $search_param = "%{$search_query}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $param_types .= 'sssss';
    }

    // Construct full query
    $query_parts[] = "FROM user_notifications un
        JOIN user_accounts ua ON un.user_id = ua.user_id
        JOIN user_details ud ON un.user_id = ud.user_id";

    // Add WHERE clause if filters exist
    if (!empty($where_clauses)) {
        $query_parts[] = "WHERE " . implode(' AND ', $where_clauses);
    }

    // Order and limit
    $query_parts[] = "ORDER BY un.created_at DESC
        LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $param_types .= 'ii';

    // Prepare and execute count query for pagination
    $count_query = str_replace(
        $query_parts[0], 
        "SELECT COUNT(*) as total_count", 
        implode(' ', $query_parts)
    );
    $count_stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_count = $count_result->fetch_assoc()['total_count'];

    // Prepare and execute main query
    $full_query = implode(' ', $query_parts);
    $stmt = $conn->prepare($full_query);
    
    // Bind parameters if any
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare notifications array
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        // Prepare profile picture
        $profile_pic = $row['profile_pic'] 
            ? 'data:image/jpeg;base64,' . base64_encode($row['profile_pic']) 
            : '../../User/images/icons/profile.png';

        $notifications[] = [
            'id' => $row['notification_id'],
            'type' => $row['type'],
            'message' => $row['message'],
            'details' => $row['additional_details'] ?? '',
            'created_at' => date('M d, Y H:i A', strtotime($row['created_at'])),
            'user' => [
                'id' => $row['user_id'],
                'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'email' => $row['email'],
                'profile_pic' => $profile_pic
            ]
        ];
    }

    // Prepare response with pagination info
    $response = [
        'success' => true,
        'notifications' => $notifications,
        'pagination' => [
            'total_count' => intval($total_count),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_count / $per_page)
        ]
    ];

    // Ensure clean output
    ob_clean();
    
    // Send successful response
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    // Log the full error details
    logError($e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Ensure clean output
    ob_clean();
    
    // Send error response
    sendJsonResponse(false, null, $e->getMessage());
} finally {
    // Close database connection if it was opened
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?> 