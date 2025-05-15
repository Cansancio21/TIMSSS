<?php
session_start();
include 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['username'])) { 
    header("Location: index.php");
    exit(); 
}

// Initialize variables for deploy form
$borrow_assetsname = $borrow_quantity = $borrow_techname = $borrow_techid = $borrow_date = "";
$borrow_assetsnameErr = $borrow_quantityErr = $borrow_technameErr = $borrow_techidErr = "";
$hasError = false;

// Initialize variables for edit form
$deploy_assetsname = $deploy_quantity = $deploy_techname = $deploy_techid = $deploy_date = "";
$deploy_assetsnameErr = $deploy_quantityErr = $deploy_technameErr = $deploy_techidErr = "";

// Handle deploy request via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deploy_asset'])) {
    $borrow_assetsname = trim($_POST['asset_name']);
    $borrow_quantity = trim($_POST['borrow_quantity']);
    $borrow_techname = trim($_POST['tech_name']);
    $borrow_techid = trim($_POST['tech_id']);
    $borrow_date = trim($_POST['date']);

    // Validate asset name
    if (!preg_match("/^[a-zA-Z\s-]+$/", $borrow_assetsname)) {
        $borrow_assetsnameErr = "Asset Name should not contain numbers.";
        $hasError = true;
    }

    // Validate technician name
    if (!preg_match("/^[a-zA-Z\s-]+$/", $borrow_techname)) {
        $borrow_technameErr = "Technician Name should not contain numbers.";
        $hasError = true;
    }

    // Validate borrow quantity
    if (!is_numeric($borrow_quantity) || $borrow_quantity <= 0) {
        $borrow_quantityErr = "Please enter a valid quantity.";
        $hasError = true;
    } 

    // Validate Technician ID
    if (!$hasError) {
        $sqlCheckTechnician = "SELECT u_id FROM tbl_user WHERE u_id = ?";
        $stmtCheckTechnician = $conn->prepare($sqlCheckTechnician);
        $stmtCheckTechnician->bind_param("s", $borrow_techid);
        $stmtCheckTechnician->execute();
        $resultCheckTechnician = $stmtCheckTechnician->get_result();

        if ($resultCheckTechnician->num_rows == 0) {
            $borrow_techidErr = "Technician ID does not exist.";
            $hasError = true;
        }
        $stmtCheckTechnician->close();
    }

    // Validate Technician Name
    if (!$hasError) {
        $sqlCheckTechName = "SELECT u_fname, u_lname FROM tbl_user WHERE u_id = ?";
        $stmtCheckTechName = $conn->prepare($sqlCheckTechName);
        $stmtCheckTechName->bind_param("s", $borrow_techid);
        $stmtCheckTechName->execute();
        $resultCheckTechName = $stmtCheckTechName->get_result();

        if ($resultCheckTechName->num_rows > 0) {
            $row = $resultCheckTechName->fetch_assoc();
            $fullName = trim($row['u_fname'] . ' ' . $row['u_lname']);
            
            if (strcasecmp($fullName, $borrow_techname) !== 0) {
                $borrow_technameErr = "Technician Name does not match the ID.";
                $hasError = true;
            }
        } else {
            $borrow_techidErr = "Technician ID does not exist.";
            $hasError = true;
        }
        $stmtCheckTechName->close();
    }

    // Check if asset exists
    if (!$hasError) {
        $sqlCheckAsset = "SELECT a_quantity FROM tbl_deployment_assets WHERE a_name = ?";
        $stmtCheckAsset = $conn->prepare($sqlCheckAsset);
        $stmtCheckAsset->bind_param("s", $borrow_assetsname);
        $stmtCheckAsset->execute();
        $resultCheckAsset = $stmtCheckAsset->get_result();

        if ($resultCheckAsset->num_rows > 0) {
            $row = $resultCheckAsset->fetch_assoc();
            $availableQuantity = $row['a_quantity'];

            if ($availableQuantity >= $borrow_quantity) {
                $sqlInsert = "INSERT INTO tbl_deployed (d_assets_name, d_quantity, d_technician_name, d_technician_id, d_date) VALUES (?, ?, ?, ?, ?)";
                $stmtInsert = $conn->prepare($sqlInsert);
                $stmtInsert->bind_param("sisis", $borrow_assetsname, $borrow_quantity, $borrow_techname, $borrow_techid, $borrow_date);

                if ($stmtInsert->execute()) {
                    $newQuantity = $availableQuantity - $borrow_quantity;
                    $sqlUpdate = "UPDATE tbl_deployment_assets SET a_quantity = ? WHERE a_name = ?";
                    $stmtUpdate = $conn->prepare($sqlUpdate);
                    $stmtUpdate->bind_param("is", $newQuantity, $borrow_assetsname);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();

                    $response = ['status' => 'success', 'message' => 'Asset deployed successfully!'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Error deploying asset: ' . $stmtInsert->error];
                }
                $stmtInsert->close();
            } else {
                $response = ['status' => 'error', 'message' => 'Not enough quantity available to deploy.'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Asset not found in the inventory.'];
        }
        $stmtCheckAsset->close();
    } else {
        $response = ['status' => 'error', 'message' => implode(' ', [$borrow_assetsnameErr, $borrow_quantityErr, $borrow_technameErr, $borrow_techidErr])];
    }

    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        $_SESSION[$response['status'] == 'success' ? 'message' : 'error'] = $response['message'];
        header("Location: deployedT.php");
        exit();
    }
}

// Handle edit request via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_asset']) && isset($_POST['d_id'])) {
    $id = (int)$_POST['d_id'];
    $deploy_assetsname = trim($_POST['asset_name']);
    $deploy_quantity = trim($_POST['deploy_quantity']);
    $deploy_techname = trim($_POST['tech_name']);
    $deploy_techid = trim($_POST['tech_id']);
    $deploy_date = trim($_POST['date']);

    // Basic validation
    $errors = [];
    $deploy_quantity = filter_var($deploy_quantity, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if (empty($deploy_assetsname)) {
        $errors[] = "Asset name is required.";
    }
    if ($deploy_quantity === false) {
        $errors[] = "Quantity must be a positive integer.";
    }
    if (empty($deploy_techname)) {
        $errors[] = "Technician name is required.";
    }
    if (empty($deploy_techid)) {
        $errors[] = "Technician ID is required.";
    }
    if (empty($deploy_date) || !strtotime($deploy_date)) {
        $errors[] = "Valid deploy date is required.";
    }

    if (empty($errors)) {
        $sqlUpdate = "UPDATE tbl_deployed SET d_assets_name = ?, d_quantity = ?, d_technician_name = ?, d_technician_id = ?, d_date = ? WHERE d_id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("sisssi", $deploy_assetsname, $deploy_quantity, $deploy_techname, $deploy_techid, $deploy_date, $id);

        if ($stmtUpdate->execute()) {
            $response = ['status' => 'success', 'message' => 'Record updated successfully!'];
        } else {
            $response = ['status' => 'error', 'message' => 'Error updating record: ' . $conn->error];
        }
        $stmtUpdate->close();
    } else {
        $response = ['status' => 'error', 'message' => implode(' ', $errors)];
    }

    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        $_SESSION[$response['status'] == 'success' ? 'message' : 'error'] = $response['message'];
        header("Location: deployedT.php?updated=true");
        exit();
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_asset']) && isset($_POST['d_id'])) {
    $id = (int)$_POST['d_id'];
    
    $sql = "DELETE FROM tbl_deployed WHERE d_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Record deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting record: " . $conn->error;
    }
    
    $stmt->close();
    header("Location: deployedT.php");
    exit();
}

// Handle AJAX request for asset name (retained for potential other uses)
if (isset($_GET['id']) && !isset($_GET['page']) && !isset($_GET['deleted']) && !isset($_GET['updated']) && !isset($_GET['action'])) {
    error_log('AJAX handler triggered for id: ' . $_GET['id']);
    header('Content-Type: application/json');
    $id = (int)$_GET['id'];
    $sql = "SELECT d_assets_name FROM tbl_deployed WHERE d_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        echo json_encode(['assetName' => null, 'error' => 'Prepare failed: ' . $conn->error]);
        $conn->close();
        exit();
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['assetName' => $row['d_assets_name']]);
    } else {
        echo json_encode(['assetName' => null]);
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

// Handle AJAX search request
if (isset($_GET['action']) && $_GET['action'] === 'search' && isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    $page = isset($_GET['search_page']) ? (int)$_GET['search_page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $output = '';

    if ($searchTerm === '') {
        // Fetch default deployed assets for the current page
        $countSql = "SELECT COUNT(*) as total FROM tbl_deployed";
        $countResult = $conn->query($countSql);
        $totalRecords = $countResult->fetch_assoc()['total'];
        $totalPages = ceil($totalRecords / $limit);

        $sql = "SELECT d_id, d_assets_name, d_quantity, d_technician_name, d_technician_id, d_date 
                FROM tbl_deployed 
                LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $offset, $limit);
    } else {
        // Count total matching records for pagination
        $countSql = "SELECT COUNT(*) as total FROM tbl_deployed 
                     WHERE d_assets_name LIKE ? OR d_technician_name LIKE ? OR d_technician_id LIKE ? OR d_date LIKE ?";
        $countStmt = $conn->prepare($countSql);
        $searchWildcard = "%$searchTerm%";
        $countStmt->bind_param("ssss", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRecords = $countResult->fetch_assoc()['total'];
        $countStmt->close();

        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated search results
        $sql = "SELECT d_id, d_assets_name, d_quantity, d_technician_name, d_technician_id, d_date 
                FROM tbl_deployed 
                WHERE d_assets_name LIKE ? OR d_technician_name LIKE ? OR d_technician_id LIKE ? OR d_date LIKE ?
                LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $offset, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output .= "<tr> 
                          <td>{$row['d_id']}</td> 
                          <td>" . (isset($row['d_assets_name']) ? htmlspecialchars($row['d_assets_name'], ENT_QUOTES, 'UTF-8') : 'N/A') . "</td>  
                          <td>{$row['d_quantity']}</td>
                          <td>" . htmlspecialchars($row['d_technician_name'], ENT_QUOTES, 'UTF-8') . "</td>
                          <td>{$row['d_technician_id']}</td>    
                          <td>{$row['d_date']}</td> 
                          <td>
                              <a class='view-btn' onclick=\"showViewModal('{$row['d_id']}', '" . htmlspecialchars($row['d_assets_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['d_quantity']}', '" . htmlspecialchars($row['d_technician_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['d_technician_id']}', '{$row['d_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                              <a class='edit-btn' onclick=\"showEditModal('{$row['d_id']}', '" . htmlspecialchars($row['d_assets_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['d_quantity']}', '" . htmlspecialchars($row['d_technician_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['d_technician_id']}', '{$row['d_date']}')\" title='Edit'><i class='fas fa-edit'></i></a>
                              <a class='delete-btn' onclick=\"showDeleteModal('{$row['d_id']}', '" . htmlspecialchars($row['d_assets_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>
                          </td>
                        </tr>";
        }
    } else {
        $output = "<tr><td colspan='7'>No deployed assets found.</td></tr>";
    }
    $stmt->close();

    // Add pagination data
    $output .= "<script>
        updatePagination($page, $totalPages, '$searchTerm');
    </script>";

    echo $output;
    exit();
}

// Get user details for the header
$username = $_SESSION['username'] ?? '';
$lastName = '';
$firstName = '';
$userType = '';
$avatarFolder = 'Uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';
$defaultAvatar = 'default-avatar.png';

if (!$username) {
    echo "Session username not set.";
    exit();
}

// Fetch user details from database
if ($conn) {
    $sql = "SELECT u_fname, u_lname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName, $userType);
    $stmt->fetch();
    $stmt->close();

    // Pagination setup
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Fetch total number of deployed assets
    $countQuery = "SELECT COUNT(*) as total FROM tbl_deployed";
    $countResult = $conn->query($countQuery);
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Fetch deployed assets with pagination
    $sqlBorrowed = "SELECT d_id, d_assets_name, d_quantity, d_technician_name, d_technician_id, d_date 
                    FROM tbl_deployed 
                    LIMIT ?, ?";
    $stmt = $conn->prepare($sqlBorrowed);
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $resultBorrowed = $stmt->get_result();
} else {
    echo "Database connection failed.";
    exit();
}

// Set avatar path
if (file_exists($userAvatar)) {
    $_SESSION['avatarPath'] = $userAvatar . '?' . time();
} else {
    $_SESSION['avatarPath'] = $defaultAvatar;
}
$avatarPath = $_SESSION['avatarPath'];

// Check for deletion or update success
if (isset($_GET['deleted']) && $_GET['deleted'] == 'true') {
    $_SESSION['message'] = "Record deleted successfully!";
}
if (isset($_GET['updated']) && $_GET['updated'] == 'true') {
    $_SESSION['message'] = "Record updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployed Assets</title>
    <link rel="stylesheet" href="deployedTB.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2><img src="image/logo.png" alt="Tix Net Icon" class="sidebar-icon">TixNet Pro</h2>
        <ul>
            <li><a href="adminD.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="viewU.php"><i class="fas fa-users"></i> <span>View Users</span></a></li>
            <li><a href="view_service_record.php"><i class="fas fa-wrench"></i> <span> Service Record</span></a></li>
            <li><a href="logs.php"><i class="fas fa-file-alt"></i> <span>View Logs</span></a></li>
            <li><a href="borrowedT.php"><i class="fas fa-book"></i> <span>Borrowed Records</span></a></li>
            <li><a href="returnT.php"><i class="fas fa-undo"></i> <span>Returned Records</span></a></li>
            <li><a href="deployedT.php" class="active"><i class="fas fa-rocket"></i> <span>Deploy Records</span></a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Deployed Assets</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search deployed assets..." onkeyup="debouncedSearchDeployed()">
                <span class="search-icon"><i class="fas fa-search"></i></span>
            </div>
            <div class="user-profile">
                <div class="user-icon">
                    <a href="image.php">
                        <?php 
                        $cleanAvatarPath = preg_replace('/\?\d+$/', '', $avatarPath);
                        if (!empty($avatarPath) && file_exists($cleanAvatarPath)) {
                            echo "<img src='" . htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') . "' alt='User Avatar'>";
                        } else {
                            echo "<i class='fas fa-user-circle'></i>";
                        }
                        ?>
                    </a>
                </div>
                <div class="user-details">
                    <span><?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <small><?php echo htmlspecialchars(ucfirst($userType), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
                <a href="settings.php" class="settings-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>
          
        <div class="alert-container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
        </div>

        <div class="table-box glass-container">
            <?php if ($userType === 'admin'): ?>
                <div class="username">
                    Welcome, <?php echo htmlspecialchars($firstName); ?>!
                    <i class="fas fa-user-shield admin-icon"></i>
                </div>
            <?php endif; ?>

            <div class="deployed">
                <div class="button-container">
                    <button class="return-btn" onclick="showDeployModal()"><i class="fas fa-cogs"></i> Deploy</button>
                    <a href="createTickets.php" class="export-btn"><i class="fas fa-download"></i> Export</a>
                </div>
                <table id="deployedTable">
                    <thead>
                        <tr>
                            <th>Deployed ID</th>
                            <th>Asset Name</th>
                            <th>Quantity</th>
                            <th>Technician Name</th>
                            <th>Technician ID</th>
                            <th>Deployed Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php 
                        if ($resultBorrowed && $resultBorrowed->num_rows > 0) { 
                            while ($row = $resultBorrowed->fetch_assoc()) { 
                                echo "<tr> 
                                        <td>{$row['d_id']}</td> 
                                        <td>" . (isset($row['d_assets_name']) ? htmlspecialchars($row['d_assets_name'], ENT_QUOTES, 'UTF-8') : 'N/A') . "</td>  
                                        <td>{$row['d_quantity']}</td>
                                        <td>" . htmlspecialchars($row['d_technician_name'], ENT_QUOTES, 'UTF-8') . "</td>
                                        <td>{$row['d_technician_id']}</td>    
                                        <td>{$row['d_date']}</td> 
                                        <td>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['d_id']}', '" . htmlspecialchars($row['d_assets_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['d_quantity']}', '" . htmlspecialchars($row['d_technician_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['d_technician_id']}', '{$row['d_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='edit-btn' onclick=\"showEditModal('{$row['d_id']}', '" . htmlspecialchars($row['d_assets_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['d_quantity']}', '" . htmlspecialchars($row['d_technician_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['d_technician_id']}', '{$row['d_date']}')\" title='Edit'><i class='fas fa-edit'></i></a>
                                            <a class='delete-btn' onclick=\"showDeleteModal('{$row['d_id']}', '" . htmlspecialchars($row['d_assets_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>
                                        </td>
                                      </tr>"; 
                            } 
                        } else { 
                            echo "<tr><td colspan='7'>No deployed assets found.</td></tr>"; 
                        } 
                        ?>
                    </tbody>
                </table>

                <div class="pagination" id="deployed-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>

                    <span class="current-page">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>       
        </div>
    </div>
</div>

<!-- Deploy Asset Modal -->
<div id="deployAssetModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Deploy Asset</h2>
        </div>
        <form method="POST" id="deployAssetForm" class="modal-form">
            <input type="hidden" name="deploy_asset" value="1">
            <input type="hidden" name="ajax" value="true">
            <label for="deploy_asset_name">Asset Name</label>
            <input type="text" name="asset_name" id="deploy_asset_name" required>
            <label for="deploy_borrow_quantity">Quantity</label>
            <input type="number" name="borrow_quantity" id="deploy_borrow_quantity" min="1" required>
            <label for="deploy_tech_name">Technician Name</label>
            <input type="text" name="tech_name" id="deploy_tech_name" required>
            <label for="deploy_tech_id">Technician ID</label>
            <input type="text" name="tech_id" id="deploy_tech_id" required>
            <label for="deploy_date">Deploy Date</label>
            <input type="date" name="date" id="deploy_date" required>
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('deployAssetModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Deploy Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>View Deployed Asset</h2>
        </div>
        <div id="viewModalContent" style="margin-top: 20px;"></div>
        <div class="modal-footer">
            <button class="modal-btn cancel" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- Edit Asset Modal -->
<div id="editAssetModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Deployed Asset</h2>
        </div>
        <form method="POST" id="editAssetForm" class="modal-form">
            <input type="hidden" name="edit_asset" value="1">
            <input type="hidden" name="ajax" value="true">
            <input type="hidden" name="d_id" id="edit_d_id">
            <label for="edit_asset_name">Asset Name</label>
            <input type="text" name="asset_name" id="edit_asset_name" required>
            <label for="edit_deploy_quantity">Quantity</label>
            <input type="number" name="deploy_quantity" id="edit_deploy_quantity" min="1" required>
            <label for="edit_tech_name">Technician Name</label>
            <input type="text" name="tech_name" id="edit_tech_name" required>
            <label for="edit_tech_id">Technician ID</label>
            <input type="text" name="tech_id" id="edit_tech_id" required>
            <label for="edit_date">Deploy Date</label>
            <input type="date" name="date" id="edit_date" required>
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('editAssetModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Update Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Delete Asset</h2>
        </div>
        <p>Are you sure you want to delete the deployed asset: <span id="deleteAssetName"></span>?</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="d_id" id="deleteAssetId">
            <input type="hidden" name="delete_asset" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentSearchPage = 1;
let defaultPage = <?php echo json_encode($page); ?>;
let updateInterval = null;

// Debounce function to limit search calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function searchDeployed(page = 1) {
    const searchTerm = document.getElementById('searchInput').value;
    const tbody = document.getElementById('tableBody');
    const paginationContainer = document.getElementById('deployed-pagination');

    currentSearchPage = page;

    // Create XMLHttpRequest for AJAX
    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            tbody.innerHTML = xhr.responseText.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        }
    };
    xhr.open('GET', `deployedT.php?action=search&search=${encodeURIComponent(searchTerm)}&search_page=${searchTerm ? page : defaultPage}`, true);
    xhr.send();
}

function updatePagination(currentPage, totalPages, searchTerm) {
    const paginationContainer = document.getElementById('deployed-pagination');
    let paginationHtml = '';

    if (currentPage > 1) {
        paginationHtml += `<a href="javascript:searchDeployed(${currentPage - 1})" class="pagination-link"><i class="fas fa-chevron-left"></i></a>`;
    } else {
        paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>`;
    }

    paginationHtml += `<span class="current-page">Page ${currentPage} of ${totalPages}</span>`;

    if (currentPage < totalPages) {
        paginationHtml += `<a href="javascript:searchDeployed(${currentPage + 1})" class="pagination-link"><i class="fas fa-chevron-right"></i></a>`;
    } else {
        paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>`;
    }

    paginationContainer.innerHTML = paginationHtml;
}

// Debounced search function
const debouncedSearchDeployed = debounce(searchDeployed, 300);

function showDeployModal() {
    document.getElementById('deployAssetForm').reset();
    document.getElementById('deployAssetModal').style.display = 'flex';
}

function showViewModal(id, assetName, quantity, technicianName, technicianId, date) {
    const modalContent = `
        <p><strong>Asset Name:</strong> ${assetName}</p>
        <p><strong>Quantity:</strong> ${quantity}</p>
        <p><strong>Technician Name:</strong> ${technicianName}</p>
        <p><strong>Technician ID:</strong> ${technicianId}</p>
        <p><strong>Deployed Date:</strong> ${date}</p>
    `;
    document.getElementById('viewModalContent').innerHTML = modalContent;
    document.getElementById('viewModal').style.display = 'flex';
}

function showEditModal(id, name, quantity, techName, techId, date) {
    document.getElementById('edit_d_id').value = id;
    document.getElementById('edit_asset_name').value = name;
    document.getElementById('edit_deploy_quantity').value = quantity;
    document.getElementById('edit_tech_name').value = techName;
    document.getElementById('edit_tech_id').value = techId;
    document.getElementById('edit_date').value = date;
    document.getElementById('editAssetModal').style.display = 'flex';
}

function showDeleteModal(id, assetName) {
    document.getElementById('deleteAssetName').textContent = assetName || 'Unknown Asset';
    document.getElementById('deleteAssetId').value = id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function updateTable() {
    const searchTerm = document.getElementById('searchInput').value;
    if (searchTerm) {
        searchDeployed(currentSearchPage);
    } else {
        fetch(`deployedT.php?page=${defaultPage}`)
            .then(response => response.text())
            .then(data => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const newTableBody = doc.querySelector('#tableBody');
                const currentTableBody = document.querySelector('#tableBody');
                currentTableBody.innerHTML = newTableBody.innerHTML;
            })
            .catch(error => console.error('Error updating table:', error));
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

// Handle deploy form submission via AJAX
document.getElementById('deployAssetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('deployedT.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const alertContainer = document.querySelector('.alert-container');
        const alert = document.createElement('div');
        alert.className = `alert alert-${data.status}`;
        alert.textContent = data.message;
        alertContainer.appendChild(alert);

        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);

        if (data.status === 'success') {
            closeModal('deployAssetModal');
            updateTable();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const alertContainer = document.querySelector('.alert-container');
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.textContent = 'An error occurred while deploying the asset.';
        alertContainer.appendChild(alert);

        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);
    });
});

// Handle edit form submission via AJAX
document.getElementById('editAssetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('deployedT.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const alertContainer = document.querySelector('.alert-container');
        const alert = document.createElement('div');
        alert.className = `alert alert-${data.status}`;
        alert.textContent = data.message;
        alertContainer.appendChild(alert);

        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);

        if (data.status === 'success') {
            closeModal('editAssetModal');
            updateTable();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const alertContainer = document.querySelector('.alert-container');
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.textContent = 'An error occurred while updating the record.';
        alertContainer.appendChild(alert);

        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);
    });
});

// Initialize auto-update table every 30 seconds
document.addEventListener('DOMContentLoaded', () => {
    updateInterval = setInterval(updateTable, 30000);

    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);
    });

    // Initialize search on page load if there's a search term
    const searchInput = document.getElementById('searchInput');
    if (searchInput.value) {
        searchDeployed();
    }
});

// Clear interval when leaving the page
window.addEventListener('beforeunload', () => {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});
</script>
</body>
</html>

<?php 
$conn->close();
?>