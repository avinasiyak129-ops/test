<?php
/**
 * DATABASE CONNECTION FILE
 * ========================
 * Configured for local XAMPP MySQL
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log
ini_set('log_errors', 1);

// Database Configuration
$host = '10.8.81.38';
$user = 'dbconnectusr';
$password = 'db@Con$ter'; // XAMPP default: no password
$database = 'opal_learninghub_live';

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'message' => 'Database Connection Failed: ' . $conn->connect_error
    ]));
}

// Set charset to utf8mb4
mysqli_set_charset($conn, 'utf8mb4');

// Set timezone
date_default_timezone_set('UTC');

// Optional: Define base URLs for local testing



?>
