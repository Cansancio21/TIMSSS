<?php
session_start();
include 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$asset_name = $quantity = $technician_name = $technician_id = $date = "";
$asset_nameErr = $quantityErr = $technician_nameErr = $technician_idErr = $dateErr = "";

// Check if the ID is set in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid or missing ID.";
    header("Location: borrowedT.php");
    exit();
}

$id = (int)$_GET['id'];
$sql = "SELECT b_assets_name, b_quantity, b_technician_name, b_technician_id, b_date FROM tbl_borrowed WHERE b_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    $_SESSION['error'] = "Database error.";
    header("Location: borrowedT.php");
    exit();
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $asset_name = $row['b_assets_name'];
    $quantity = $row['b_quantity'];
    $technician_name = $row['b_technician_name'];
    $technician_id = $row['b_technician_id'];
    $date = $row['b_date'];
} else {
    $_SESSION['error'] = "Record not found.";
    header("Location: borrowedT.php");
    exit();
}
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $asset_name = trim($_POST['asset_name']);
    $quantity = trim($_POST['quantity']);
    $technician_name = trim($_POST['technician_name']);
    $technician_id = trim($_POST['technician_id']);
    $date = trim($_POST['date']);

    // Validate inputs
    if (empty($asset_name)) {
        $asset_nameErr = "Asset name is required.";
    } elseif (strlen($asset_name) > 100) {
        $asset_nameErr = "Asset name must be 100 characters or less.";
    }

    if (empty($quantity)) {
        $quantityErr = "Quantity is required.";
    } elseif (!is_numeric($quantity) || $quantity <= 0) {
        $quantityErr = "Quantity must be a positive number.";
    }

    if (empty($technician_name)) {
        $technician_nameErr = "Technician name is required.";
    } elseif (strlen($technician_name) > 100) {
        $technician_nameErr = "Technician name must be 100 characters or less.";
    }

    if (empty($technician_id)) {
        $technician_idErr = "Technician ID is required.";
    } elseif (strlen($technician_id) > 50) {
        $technician_idErr = "Technician ID must be 50 characters or less.";
    }

    if (empty($date)) {
        $dateErr = "Date is required.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
        $dateErr = "Invalid date format.";
    }

    // If no validation errors, update the record
    if (empty($asset_nameErr) && empty($quantityErr) && empty($technician_nameErr) && empty($technician_idErr) && empty($dateErr)) {
        $sqlUpdate = "UPDATE tbl_borrowed SET b_assets_name = ?, b_quantity = ?, b_technician_name = ?, b_technician_id = ?, b_date = ? WHERE b_id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if (!$stmtUpdate) {
            error_log("Prepare failed: " . $conn->error);
            $_SESSION['error'] = "Database error.";
            header("Location: borrowedT.php");
            exit();
        }
        $stmtUpdate->bind_param("sissis", $asset_name, $quantity, $technician_name, $technician_id, $date, $id);

        if ($stmtUpdate->execute()) {
            $_SESSION['message'] = "Record updated successfully!";
            header("Location: borrowedT.php?updated=true");
            exit();
        } else {
            error_log("Execution failed: " . $stmtUpdate->error);
            $_SESSION['error'] = "Error updating record: " . $stmtUpdate->error;
        }
        $stmtUpdate->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Borrowed Asset</title>
    <link rel="stylesheet" href="return.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .error {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
            display: block;
        }
        .form-row input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-row input[type="number"] {
            -webkit-appearance: none;
            -moz-appearance: textfield;
            appearance: none;
        }
        .form-row input[type="number"]::-webkit-inner-spin-button,
        .form-row input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <a href="borrowedT.php" class="back-icon">
                <i class='bx bx-arrow-back'></i>
            </a>
            <h1>Edit Borrowed Asset</h1>

            <form method="POST" action="" class="form">
                <div class="form-row">
                    <label for="asset_name">Asset Name:</label>
                    <input type="text" id="asset_name" name="asset_name" placeholder="Asset Name" value="<?php echo htmlspecialchars($asset_name, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="error"><?php echo $asset_nameErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" placeholder="Quantity" value="<?php echo htmlspecialchars($quantity, ENT_QUOTES, 'UTF-8'); ?>" min="1">
                    <span class="error"><?php echo $quantityErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="technician_name">Technician Name:</label>
                    <input type="text" id="technician_name" name="technician_name" placeholder="Technician Name" value="<?php echo htmlspecialchars($technician_name, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="error"><?php echo $technician_nameErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="technician_id">Technician ID:</label>
                    <input type="text" id="technician_id" name="technician_id" placeholder="Technician ID" value="<?php echo htmlspecialchars($technician_id, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="error"><?php echo $technician_idErr; ?></span>
                </div>
                <div class="form-row">
                    <label for="date">Borrowed Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="error"><?php echo $dateErr; ?></span>
                </div>
                <button type="submit">Update</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>