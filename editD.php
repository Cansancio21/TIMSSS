<?php
session_start();
include 'db.php';

// Initialize variables
$return_assetsname = $return_quantity = $return_techname = $return_techid = $return_date = "";
$return_assetsnameErr = $return_quantityErr = $return_technameErr = $return_techidErr = "";

// Check if the ID is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT r_assets_name, r_quantity, r_technician_name, r_technician_id, r_date FROM tbl_returned WHERE r_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $return_assetsname = $row['r_assets_name'];
        $return_quantity = $row['r_quantity'];
        $return_techname = $row['r_technician_name'];
        $return_techid = $row['r_technician_id'];
        $return_date = $row['r_date'];
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $return_assetsname = trim($_POST['asset_name']);
    $return_quantity = trim($_POST['return_quantity']);
    $return_techname = trim($_POST['tech_name']);
    $return_techid = trim($_POST['tech_id']);
    $return_date = trim($_POST['date']);

    // Validate inputs (add your validation logic here)

    // Update the record in the database
    $sqlUpdate = "UPDATE tbl_returned SET r_assets_name = ?, r_quantity = ?, r_technician_name = ?, r_technician_id = ?, r_date = ? WHERE r_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("sssssi", $return_assetsname, $return_quantity, $return_techname, $return_techid, $return_date, $id);

    if ($stmtUpdate->execute()) {
        echo "<script type='text/javascript'>
                alert('Record updated successfully.');
                window.location.href = 'deployedT.php';
              </script>";
    } else {
        die("Execution failed: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Returned Asset</title>
    <link rel="stylesheet" href="return.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <a href="returnT.php" class="back-icon">
                <i class='bx bx-arrow-back'></i>
            </a>
            <h1>Edit Returned Asset:</h1>

            <form method="POST" action="" class="form">
                <div class="form-row">
                    <label for="asset_name">Asset Name:</label>
                    <input type="text" id="asset_name" name="asset_name" placeholder="Asset Name" value="<?php echo htmlspecialchars($return_assetsname); ?>" required>
                    <span class="error"><?php echo $return_assetsnameErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="return_quantity">Enter Quantity Returned:</label>
                    <input type="text" id="return_quantity" name="return_quantity" placeholder="Quantity" value="<?php echo htmlspecialchars($return_quantity); ?>" required>
                    <span class="error"><?php echo $return_quantityErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="tech_name">Enter Technician Name:</label>
                    <input type="text" id="tech_name" name="tech_name" placeholder="Technician Name" value="<?php echo htmlspecialchars($return_techname); ?>" required>
                    <span class="error"><?php echo $return_technameErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="tech_id">Enter Technician Id:</label>
                    <input type="text" id="tech_id" name="tech_id" placeholder="Technician Id" value="<?php echo htmlspecialchars($return_techid); ?>" required>
                    <span class="error"><?php echo $return_techidErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="date">Date Returned:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($return_date); ?>" required>
                </div>
                <button type="submit">Update</button>
            </form>
        </div>
    </div>
</body>
</html>