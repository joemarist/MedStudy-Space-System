<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "medstudy";

// Get the absolute path of the project root
$projectRoot = dirname(__FILE__);

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!$conn->query("CREATE DATABASE IF NOT EXISTS $dbname")) {
    die("Database creation failed: " . $conn->error);
}

$conn->select_db($dbname);

// Construct the full path to database.sql
$sqlFile = $projectRoot . DIRECTORY_SEPARATOR . "database.sql";

// Check if the file exists with detailed error reporting
if (!file_exists($sqlFile)) {
    // Log the error and current directory structure
    error_log("SQL file not found at: " . $sqlFile);
    error_log("Project root directory: " . $projectRoot);
    error_log("Directory contents: " . print_r(scandir($projectRoot), true));
    
    // Try alternative paths
    $alternativePaths = [
        dirname($projectRoot) . DIRECTORY_SEPARATOR . "database.sql",
        $projectRoot . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "database.sql"
    ];
    
    $foundFile = false;
    foreach ($alternativePaths as $path) {
        if (file_exists($path)) {
            $sqlFile = $path;
            $foundFile = true;
            break;
        }
    }
    
    if (!$foundFile) {
        die("SQL file not found. Searched locations: " . implode(", ", array_merge([$sqlFile], $alternativePaths)));
    }
}

// Read and execute SQL file
$sql = file_get_contents($sqlFile);

// Check if tables already exist to prevent re-creating
$requiredTables = [
    'user_accounts', 
    'user_details', 
    'rooms', 
    'booking', 
    'cancelledBooking_Logs'
];

$tablesExist = true;
foreach ($requiredTables as $table) {
    $checkTableQuery = "SHOW TABLES LIKE '$table'";
    $result = $conn->query($checkTableQuery);
    
    if ($result->num_rows == 0) {
        $tablesExist = false;
        break;
    }
}

if (!$tablesExist) {
    // Tables don't exist or incomplete, so execute the SQL script
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } else {
        die("Error executing SQL script: " . $conn->error);
    }
}

// Keep the connection open for subsequent database operations
$conn->close();
?>