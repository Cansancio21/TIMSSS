<?php
session_start();
include 'db.php';

// Initialize variables
$borrow_assetsname = $borrow_quantity = $borrow_techname = $borrow_techid = $borrow_date = "";
$borrow_assetsnameErr = $borrow_quantityErr = $borrow_technameErr = $borrow_techidErr = "";

// Check if the ID is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT b_assets_name, b_quantity, b_technician_name, b_technician_id, b_date FROM tbl_borrowed WHERE b_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $borrow_assetsname = $row['b_assets_name'];
        $borrow_quantity = $row['b_quantity'];
        $borrow_techname = $row['b_technician_name'];
        $borrow_techid = $row['b_technician_id'];
        $borrow_date = $row['b_date'];
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $borrow_assetsname = trim($_POST['asset_name']);
    $borrow_quantity = trim($_POST['borrow_quantity']);
    $borrow_techname = trim($_POST['tech_name']);
    $borrow_techid = trim($_POST['tech_id']);
    $borrow_date = trim($_POST['date']);

    // Validate inputs (add your validation logic here)

    // Update the record in the database
    $sqlUpdate = "UPDATE tbl_borrowed SET b_assets_name = ?, b_quantity = ?, b_technician_name = ?, b_technician_id = ?, b_date = ? WHERE b_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("sssssi", $borrow_assetsname, $borrow_quantity, $borrow_techname, $borrow_techid, $borrow_date, $id);

    if ($stmtUpdate->execute()) {
        echo "<script type='text/javascript'>
                alert('Record updated successfully.');
                window.location.href = 'borrowedT.php'; // Redirect to borrowedT.php
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
    <title>Borrow Asset</title>
    <link rel="stylesheet" href="borrow.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <a href="borrowedT.php" class="back-icon">
                <i class='bx bx-arrow-back'></i>
            </a>
            <h1>Enter Details to Borrow:</h1>

            <form method="POST" action="" class="form">
                <div class="form-row">
                    <label for="asset_name">Asset Name:</label>
                    <input type="text" id="asset_name" name="asset_name" placeholder="Asset Name" value="<?php echo htmlspecialchars($borrow_assetsname); ?>" required>
                    <span class="error"><?php echo $borrow_assetsnameErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="borrow_quantity">Enter Quantity to Borrow:</label>
                    <input type="text" id="borrow_quantity" name="borrow_quantity" placeholder="Quantity" value="<?php echo htmlspecialchars($borrow_quantity); ?>" required>
                    <span class="error"><?php echo $borrow_quantityErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="tech_name">Enter Technician Name:</label>
                    <input type="text" id="tech_name" name="tech_name" placeholder="Technician Name" value="<?php echo htmlspecialchars($borrow_techname); ?>" required>
                    <span class="error"><?php echo $borrow_technameErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="tech_id">Enter Technician Id:</label>
                    <input type="text" id="tech_id" name="tech_id" placeholder="Technician Id" value="<?php echo htmlspecialchars($borrow_techid); ?>" required>
                    <span class="error"><?php echo $borrow_techidErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="date">Date Borrowed:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($borrow_date); ?>" required>
                </div>
                <button type="submit">Enter</button>
            </form>
        </div>
    </div>
</body>
</html>