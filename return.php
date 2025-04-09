<?php
session_start();
include 'db.php';

// Initialize variables
$return_assetsname = "";
$returnquantity = "";
$return_techname = "";
$return_techid = "";
$returndate = "";
$return_assetsnameErr = "";
$return_techidErr = "";
$return_technameErr = "";
$return_quantityErr = "";
$hasError = false;
$successMessage = "";

// Check if the database connection is established
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// User Registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $return_assetsname = trim($_POST['asset_name']);
    $returnquantity = trim($_POST['borrow_quantity']);
    $return_techname = trim($_POST['tech_name']);
    $return_techid = trim($_POST['tech_id']);
    $returndate = trim($_POST['date']);

    // Validate asset name (should not contain numbers)
    if (preg_match('/\d/', $return_assetsname)) {
        $return_assetsnameErr = "Asset name should not contain numbers.";
        $hasError = true;
    }

    // Validate technician name (should not contain numbers)
    if (preg_match('/\d/', $return_techname)) {
        $return_technameErr = "Technician name should not contain numbers.";
        $hasError = true;
    }

    // Validate quantity (should be numeric)
    if (!is_numeric($returnquantity) || $returnquantity < 1) {
        $return_quantityErr = "Quantity must be a number.";
        $hasError = true;
    }

    // Validate technician ID (should be numeric)
    if (!is_numeric($return_techid)) {
        $return_techidErr = "Technician ID must be a number.";
        $hasError = true;
    }

    // Debugging: Log input values
    error_log("Asset Name: $return_assetsname, Technician Name: $return_techname, Technician ID: $return_techid");

   // Validate asset name in the database
     if (!$hasError) {
    // Modify the SQL query to sum up the quantities of the same asset name across different b_id values
    $sqlCheckAsset = "SELECT SUM(b_quantity) AS total_quantity FROM tbl_borrowed WHERE b_assets_name = ? AND b_quantity > 0"; 
    $stmtCheckAsset = $conn->prepare($sqlCheckAsset);
    $stmtCheckAsset->bind_param("s", $return_assetsname);
    $stmtCheckAsset->execute();
    $resultCheckAsset = $stmtCheckAsset->get_result();

    if ($resultCheckAsset->num_rows == 0) {
        $return_assetsnameErr = "Asset not found in the borrowed records.";
        $hasError = true;
    } else {
        $row = $resultCheckAsset->fetch_assoc();
        $availableQuantity = $row['total_quantity'];  // Now we have the total available quantity

        // Debugging: Log the available quantity
        error_log("Available Quantity: $availableQuantity");

        // Check if quantity to return is valid
        if ($availableQuantity <= 0) {
            $return_quantityErr = "There are no assets to return.";
            $hasError = true;
        } elseif ($returnquantity > $availableQuantity) {
            $return_quantityErr = "Return quantity exceeds borrowed quantity.";
            $hasError = true;
        }
    }
    $stmtCheckAsset->close();
}


    // Validate Technician ID
    if (!$hasError) {
        $sqlCheckTechnician = "SELECT u_id FROM tbl_user WHERE u_id = ?";
        $stmtCheckTechnician = $conn->prepare($sqlCheckTechnician);
        $stmtCheckTechnician->bind_param("s", $return_techid);
        $stmtCheckTechnician->execute();
        $resultCheckTechnician = $stmtCheckTechnician->get_result();
        if ($resultCheckTechnician->num_rows == 0) {
            $return_techidErr = "Technician ID does not exist.";
            $hasError = true;
        }
        $stmtCheckTechnician->close();
    }

    // Validate Technician Name
    if (!$hasError) {
        $sqlCheckTechName = "SELECT u_fname, u_lname FROM tbl_user WHERE u_id = ?";
        $stmtCheckTechName = $conn->prepare($sqlCheckTechName);
        $stmtCheckTechName->bind_param("s", $return_techid);
        $stmtCheckTechName->execute();
        $resultCheckTechName = $stmtCheckTechName->get_result();
        if ($resultCheckTechName->num_rows > 0) {
            $row = $resultCheckTechName->fetch_assoc();
            $fullName = trim($row['u_fname'] . ' ' . $row['u_lname']);
            if (strcasecmp($fullName, $return_techname) !== 0) {
                $return_technameErr = "Technician Name does not exist.";
                $hasError = true;
            }
        } else {
            $return_techidErr = "Technician ID does not exist.";
            $hasError = true;
        }
        $stmtCheckTechName->close();
    }

    // Process return if no errors
    if (!$hasError) {
        // Insert into the returned table
        $sqlInsert = "INSERT INTO tbl_returned (r_assets_name, r_quantity, r_technician_name, r_technician_id, r_date) VALUES (?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("sisis", $return_assetsname, $returnquantity, $return_techname, $return_techid, $returndate);
        
        if ($stmtInsert->execute()) {
            // Update the quantity in the tbl_borrowed table
            $newQuantity = $availableQuantity - $returnquantity;
            $sqlUpdateBorrowed = "UPDATE tbl_borrowed SET b_quantity = ? WHERE b_assets_name = ? AND b_technician_name = ? AND b_technician_id = ?";
            $stmtUpdateBorrowed = $conn->prepare($sqlUpdateBorrowed);
            $stmtUpdateBorrowed->bind_param("issi", $newQuantity, $return_assetsname, $return_techname, $return_techid);

            // Execute the update for tbl_borrowed
            if (!$stmtUpdateBorrowed->execute()) {
                die("Update failed for tbl_borrowed: " . $stmtUpdateBorrowed->error);
            }
            $stmtUpdateBorrowed->close();

       // Fetch the current quantity in tbl_borrow_assets before updating
       $sqlCheckAssets = "SELECT a_quantity FROM tbl_borrow_assets WHERE a_name = ?";
       $stmtCheckAssets = $conn->prepare($sqlCheckAssets);
       $stmtCheckAssets->bind_param("s", $return_assetsname);
       $stmtCheckAssets->execute();
       $resultCheckAssets = $stmtCheckAssets->get_result();

if ($resultCheckAssets->num_rows > 0) {
    $row = $resultCheckAssets->fetch_assoc();
    $currentAssetsQuantity = $row['a_quantity'];
    // Adjust the new quantity for tbl_borrow_assets based on this.
    $updatedAssetsQuantity = $currentAssetsQuantity + $returnquantity; // Assuming it's a return.
    
    // Update the tbl_borrow_assets with new quantity
    $sqlUpdateAssets = "UPDATE tbl_borrow_assets SET a_quantity = ? WHERE a_name = ?";
    $stmtUpdateAssets = $conn->prepare($sqlUpdateAssets);
    $stmtUpdateAssets->bind_param("is", $updatedAssetsQuantity, $return_assetsname);

    if ($stmtUpdateAssets->execute()) {
        error_log("tbl_borrow_assets updated successfully with new quantity: $updatedAssetsQuantity");
    } else {
        error_log("Failed to update tbl_borrow_assets: " . $stmtUpdateAssets->error);
    }
    $stmtUpdateAssets->close();
} else {
    error_log("Asset not found in tbl_borrow_assets.");
}

            // Redirect after successful operation
            header("Location: assetsT.php");
            exit(); // Ensure no further code is executed
        } else {
            die("Insert failed: " . $stmtInsert->error);
        }
        $stmtInsert->close();
    }
}

// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Asset</title>
    <link rel="stylesheet" href="return.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <a href="assetsT.php" class="back-icon">
                <i class='bx bx-arrow-back'></i>
            </a>
            <h1>Enter Details to Return:</h1>

            <form method="POST" action="" class="form">
                <div class="form-row">
                    <label for="asset_name">Asset Name:</label>
                    <input type="text" id="asset_name" name="asset_name" placeholder="Asset Name" required>
                    <span class="error"><?php echo $return_assetsnameErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="borrow_quantity">Enter Quantity to Return:</label>
                    <input type="text" id="borrow_quantity" name="borrow_quantity" placeholder="Quantity" required>
                    <span class="error"><?php echo $return_quantityErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="tech_name">Enter Technician Name:</label>
                    <input type="text" id="tech_name" name="tech_name" placeholder="Technician Name" required>
                    <span class="error"><?php echo $return_technameErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="tech_id">Enter Technician Id:</label>
                    <input type="text" id="tech_id" name="tech_id" placeholder="Technician Id" required>
                    <span class="error"><?php echo $return_techidErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="date">Date Returned:</label>
                    <input type="date" id="date" name="date" required>
                </div>
                <button type="submit">Enter</button>
            </form>
        </div>
    </div>
</body>
</html>