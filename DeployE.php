<?php 
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) { 
    header("Location: index.php"); // Redirect to login page if not logged in 
    exit(); 
}

// Check if the asset ID is provided
if (isset($_GET['id'])) {
    $assetId = $_GET['id'];

    // Fetch asset details based on the asset ID
    $sql = "SELECT a_id, a_name, a_status, a_quantity, a_date FROM tbl_deployment_assets WHERE a_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assetId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $asset = $result->fetch_assoc();
    } else {
        echo "Asset not found.";
        exit();
    }
} else {
    echo "No asset ID provided.";
    exit();
}

// Handle form submission for updating the asset
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assetName = $_POST['asset_name'];
    $assetStatus = $_POST['asset_status'];
    $assetQuantity = $_POST['asset_quantity'];
    $assetDate = $_POST['asset_date'];

    // Update the asset in the database
    $sqlUpdate = "UPDATE tbl_deployment_assets SET a_name = ?, a_status = ?, a_quantity = ?, a_date = ? WHERE a_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ssisi", $assetName, $assetStatus, $assetQuantity, $assetDate, $assetId);
    
    if ($stmtUpdate->execute()) {
        echo "<script>alert('Asset updated successfully!'); window.location.href='assetsT.php';</script>";
    } else {
        echo "<script>alert('Error updating asset.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Deployment Asset</title>
    <link rel="stylesheet" href="BorrowE.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <a href="assetsT.php" class="back-icon">
                <i class='bx bx-arrow-back'></i>
            </a>
            <h1>Edit Deployment Asset</h1>
            <form method="POST" action="" class="form">
                <div class="form-row">
                    <label for="asset_name">Asset Name:</label>
                    <input type="text" id="asset_name" name="asset_name" value="<?php echo htmlspecialchars($asset['a_name']); ?>" required>
                </div>
                <div class="form-row">
                    <label for="asset_status">Status:</label>
                    <select id="asset_status" name="asset_status" required>
                        <option value="Available" <?php echo ($asset['a_status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                        <option value="Borrowed" <?php echo ($asset['a_status'] == 'Borrowed') ? 'selected' : ''; ?>>Borrowed</option>
                        <option value="Under Maintenance" <?php echo ($asset['a_status'] == 'Under Maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="asset_quantity">Quantity:</label>
                    <input type="number" id="asset_quantity" name="asset_quantity" value="<?php echo htmlspecialchars($asset['a_quantity']); ?>" min="0" required>
                </div>
                <div class="form-row">
                    <label for="asset_date">Date:</label>
                    <input type="date" id="asset_date" name="asset_date" value="<?php echo htmlspecialchars($asset['a_date']); ?>" required>
                </div>
            
                <button type="submit">Update Asset</button>
            </form>
        </div>
    </div>
</body>
</html>

