<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "medstudy";

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!$conn->query("CREATE DATABASE IF NOT EXISTS $dbname")) {
    die("Database creation failed: " . $conn->error);
}

$conn->select_db($dbname);

$sqlFile = "database.sql";
if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile");
}

$sql = file_get_contents($sqlFile);

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
} else {
    die("Error executing SQL script: " . $conn->error);
}

$conn->close();
?>