<?php
// Database connection details
$host = "localhost"; // If using XAMPP, this is correct
$db_name = "cocs"; // Your database name
$username = "root"; // Default XAMPP MySQL username
$password = ""; // Default is empty in XAMPP

// Create a connection
$conn = new mysqli($host, $username, $password, $db_name);

// Check if connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>