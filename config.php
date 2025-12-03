<?php
/**
 * Database Configuration
 */

$servername = "localhost";
$username = "root";
$password = "";
$database = "dashcamera_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed. Please try again later.");
}

// Set charset for proper encoding
$conn->set_charset("utf8mb4");