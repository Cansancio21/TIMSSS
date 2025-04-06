<?php 
include 'db.php';
session_start(); 

// Check if the user is logged in
if (!isset($_SESSION['username'])) { 
    header("Location: index.php"); // Redirect to login page if not logged in 
    exit(); 
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
    <link rel="stylesheet" href="technicianD.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css"> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 
  
</head>
<body>
<div class="wrapper">
    <div class="sidebar">
        <h2>Task Management</h2>
        <ul>
        <li><a href="adminD.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="staffD.php"><i class="fas fa-users-cog"></i> View Staff Created Tickets </a></li>
        <li><a href="suppT.php"><i class="fas fa-clipboard-list"></i> View Portal Tickets </a></li>
        <li><a href="view_incident_report.php"><i class="fas fa-bell"></i> Report Assets Deployment</a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper">
            <h1>Dashboard</h1>
            <div class="search-container">
                <input type="text" class="search-bar" placeholder="Search...">
                <span class="search-icon">🔍</span>
                <a href="settings.php" class="settings-link">
                    <i class='bx bx-cog'></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>

       
</body>
</html>

<?php 
$conn->close(); // Close the database connection 
?>