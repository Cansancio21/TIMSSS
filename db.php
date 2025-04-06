<?php
$host = "localhost";  // Change if needed
$user = "root";       // Your database username
$pass = "";           // Your database password (default is empty for XAMPP)
$dbname = "task_management"; // Your database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

