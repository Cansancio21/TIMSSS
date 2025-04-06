<?php
session_start();
include 'db.php';

// Initialize variables
$assetsName = '';
$assetsStatus = '';


if ($conn) {
    
    $sqlBorrowed = "SELECT a_id, a_name, a_status, a_quantity, a_date FROM tbl_borrow_assets"; // Fetch borrowed assets
    $resultBorrowed = $conn->query($sqlBorrowed); 

    
    $sqlDeployment = "SELECT a_id, a_name, a_status, a_quantity, a_date FROM tbl_deployment_assets"; // Fetch deployment assets
    $resultDeployment = $conn->query($sqlDeployment); 

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
    <link rel="stylesheet" href="assetT.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
</head>
<body>
<div class="wrapper">
    <div class="sidebar">
        <h2>Task Management</h2>
        <ul>
            <li><a href="staffD.php"><i class="fas fa-ticket-alt"></i> View Tickets</a></li>
            <li><a href="view_service_record.php"><i class="fas fa-box"></i> View Assets</a></li>
            <li><a href="createTickets.php"><i class="fas fa-file-invoice"></i> Ticket Registration</a></li>
            <li><a href="addC.php"><i class="fas fa-user-plus"></i> Add Customer</a></li>
            <li><a href="createTickets.php"><i class="fas fa-user-plus"></i> Ticket Registration</a></li>     
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper">
            <h1>Assets Info</h1>
        </div>  
        <div class="search-container">
            <input type="text" class="search-bar" placeholder="Search...">
            <span class="search-icon">🔍</span> <!-- Replace with your icon -->
        </div>
        
        <div class="table-box">
            <div class="borrow">
            <a href="registerAssets.php" class="add-btn"><i class="fas fa-user-plus"></i> Add Assets</a>
            <a href="createTickets.php" class="export-btn"><i class="fas fa-download"></i>Export</a>
            <table>
            <h2>Registered Assets</h2>
            <table>
                <thead>
                    <tr>
                        <th>Asset Id</th>
                        <th>Asset Name</th>
                        <th>Asset Status</th>
                        <th>Asset Quantity</th>
                        <th>Date Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($resultBorrowed && $resultBorrowed->num_rows > 0) { 
                        while ($row = $resultBorrowed->fetch_assoc()) { 
                            echo "<tr> 
                                    <td>{$row['a_id']}</td> 
                                    <td>{$row['a_name']}</td>  
                                    <td>{$row['a_status']}</td>
                                    <td>{$row['a_quantity']}</td>  
                                    <td>{$row['a_date']}</td> 
                                    <td>
                                        <a href='editC.php?id={$row['a_id']}'><i class='fas fa-edit'></i></a>
                                        <a href='deleteC.php?id={$row['a_id']}'><i class='fas fa-trash'></i></a>
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

            <div class="deploy">
            <table>
                <thead>
                    <tr>
                        <th>Asset Id</th>
                        <th>Asset Name</th>
                        <th>Asset Status</th>
                        <th>Asset Quantity</th>
                        <th>Date Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($resultDeployment && $resultDeployment->num_rows > 0) { 
                        while ($row = $resultDeployment->fetch_assoc()) { 
                            echo "<tr> 
                                    <td>{$row['a_id']}</td> 
                                    <td>{$row['a_name']}</td>  
                                    <td>{$row['a_status']}</td>
                                    <td>{$row['a_quantity']}</td>  
                                    <td>{$row['a_date']}</td> 
                                    <td>
                                        <a href='editC.php?id={$row['a_id']}'><i class='fas fa-edit'></i></a>
                                        <a href='deleteC.php?id={$row['a_id']}'><i class='fas fa-trash'></i></a>
                                    </td>
                                  </tr>"; 
                        } 
                    } else { 
                        echo "<tr><td colspan='5'>No deployment assets found.</td></tr>"; 
                    } 
                    ?>
                </tbody>
            </table>
            <a href="borrowA.php" class="borrow-btn"><i class="fas fa-plus"></i>Borrow</a>
            <a href="return.php" class="return-btn"><i class="fas fa-undo"></i>Return</a>
            </div>
           

            </div>

    </div>
</div>
</body>
</html>

<?php 
$conn->close(); 
?>