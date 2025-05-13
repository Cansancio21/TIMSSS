<?php
session_start(); // Start session for login management
include 'db.php';

// Initialize variables
$assetname = "";
$assetstatus = "";
$assetquantity = ""; 
$assetdate = "";
$assetnameErr = "";
$assetstatusError = "";
$hasError = false; // Initialize hasError to false
$successMessage = "";

// Check if the database connection is established
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// User Registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $assetname = trim($_POST['asset_name']);
    $assetstatus = trim($_POST['asset_status']);
    $assetquantity = trim($_POST['asset_quantity']);
    $assetdate = trim($_POST['date']);

    // Validate asset name
    if (!preg_match("/^[a-zA-Z\s-]+$/", $assetname)) {
        $assetnameErr = "Asset Name should not contain numbers.";
        $hasError = true;
    }

    // Insert into the appropriate table based on asset status
    if (!$hasError) {
        if ($assetstatus == "Borrowing") {
            $sql = "INSERT INTO tbl_borrow_assets (a_name, a_status, a_quantity, a_date) VALUES (?, ?, ?, ?)";
        } else {
            $sql = "INSERT INTO tbl_deployment_assets (a_name, a_status, a_quantity, a_date) VALUES (?, ?, ?, ?)";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        // Bind parameters correctly
        $stmt->bind_param("ssss", $assetname, $assetstatus, $assetquantity, $assetdate);

        if ($stmt->execute()) {
            echo "<script type='text/javascript'>
                    alert('Assets have been registered successfully.');
                    window.location.href = 'assetsT.php'; // Redirect to assets.php
                  </script>";
        } else {
            die("Execution failed: " . $stmt->error);
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Registration</title>
    <link rel="stylesheet" href="registerAssets.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
     <div class="wrapper">
       <div class="container">
       <a href="staffD.php" class="back-icon">
            <i class='bx bx-arrow-back'></i>
        </a>
        <h1>Assets Registration</h1>
        <form method="POST" action="" class="form">
                <div class="form-row">
                    <label for="asset_name">Asset Name:</label>
                    <input type="text" id="asset_name" name="asset_name" placeholder="Asset Name" required>
                </div>
                <div class="form-row">
                    <label for="asset_quantity">Asset Quantity to Register:</label>
                    <input type="text" id="asset_quantity" name="asset_quantity" placeholder="Asset quantity" required>
                </div>
                <div class="form-row">
                    <label for="asset_status">Asset Status:</label>
                    <select id="asset_status" name="asset_status" required>
                        <option value="Borrowing">For Borrowing</option>
                        <option value="Deployment">For Deployment</option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="date">Date Registered:</label>
                    <input type="date" id="date" name="date" required>
                </div>
            
            <button type="submit">Register Assets</button>
        </form>
     </div>
</body>
</html>
