<?php
session_start();
include 'db.php';

// Initialize variables
$assetsName = '';
$assetsStatus = '';

// Check if the database connection is established
if ($conn) {
    $sqlBorrowed = "SELECT b_id, b_assets_name, b_quantity, b_technician_name, b_technician_id, b_date FROM tbl_borrowed";
    $resultBorrowed = $conn->query($sqlBorrowed); 


} else {
    echo "Database connection failed.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Registration</title>
    <link rel="stylesheet" href="borrowedTA.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
</head>
<body>
<div class="wrapper">
    <div class="sidebar">
        <h2>Task Management</h2>
        <ul>
        <li><a href="adminD.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="viewU.php"><i class="fas fa-users-cog"></i> View Users</a></li>
        <li><a href="view_service_record.php"><i class="fas fa-clipboard-list"></i> View Service Record</a></li>
        <li><a href="view_incident_report.php"><i class="fas fa-bell"></i> View Incident Report</a></li>
        <li><a href="view_logs.php"><i class="fas fa-history"></i> View Logs</a></li>
        <li><a href="returnT.php"><i class="fas fa-undo"></i> Return Records</a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
        </footer>
    </div>

    <div class="container">

        <div class="upper">
            <h1>BASTA SA BORROWED NI</h1>
        </div>  

        <div class="search-container">
            <input type="text" class="search-bar" placeholder="Search...">
            <span class="search-icon">🔍</span> <!-- Replace with your icon -->
        </div>
        
        <div class="table-box">
            <div class="borrowed">
            <a href="borrowA.php" class="borrow-btn"><i class="fas fa-plus"></i> Borrow</a>
            <a href="createTickets.php" class="export-btn"><i class="fas fa-download"></i>Export</a>
            <table>
            <h2>Borrowed Assets</h2>
            <table>
                <thead>
                    <tr>
                        <th>Borrowed Id</th>
                        <th>Asset Name</th>
                        <th>Quantity</th>
                        <th>Technician Name</th>
                        <th>Technician ID</th>
                        <th>Borrowed Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($resultBorrowed && $resultBorrowed->num_rows > 0) { 
                        while ($row = $resultBorrowed->fetch_assoc()) { 
                            echo "<tr> 
                                    <td>{$row['b_id']}</td> 
                                    <td>{$row['b_assets_name']}</td>  
                                    <td>{$row['b_quantity']}</td>
                                    <td>{$row['b_technician_name']}</td>
                                    <td>{$row['b_technician_id']}</td>    
                                    <td>{$row['b_date']}</td> 
                                    <td>
                                        <a href='editR.php?id={$row['b_id']}'><i class='fas fa-edit'></i></a>
                                        <a href='deleteR.php?id={$row['b_id']}'><i class='fas fa-trash'></i></a>
                                    </td>
                                  </tr>"; 
                        } 
                    } else { 
                        echo "<tr><td colspan='5'>No borrowed assets found.</td></tr>"; 
                    } 
                    ?>
                </tbody>
            </table>
            </div>       
             
    </div>

</div>
</body>
</html>

<?php 
$conn->close();
?>

