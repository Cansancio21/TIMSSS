<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Initialize user variables
$username = $_SESSION['username'];
$firstName = '';
$userType = '';
$avatarPath = 'default-avatar.png';
$avatarFolder = 'Uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';

// Set avatar path
if (file_exists($userAvatar)) {
    $avatarPath = $userAvatar;
} else {
    $avatarPath = 'default-avatar.png';
}

// Fetch user details
if ($conn) {
    $sqlUser = "SELECT u_fname, u_type FROM tbl_user WHERE u_username = ?";
    $stmtUser = $conn->prepare($sqlUser);
    if ($stmtUser) {
        $stmtUser->bind_param("s", $username);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        if ($resultUser->num_rows > 0) {
            $row = $resultUser->fetch_assoc();
            $firstName = $row['u_fname'];
            $userType = $row['u_type'];
        }
        $stmtUser->close();
    } else {
        $_SESSION['error'] = "Error preparing user query.";
    }
} else {
    $_SESSION['error'] = "Database connection failed.";
}

// Handle AJAX search request
if (isset($_GET['action']) && $_GET['action'] === 'search' && isset($_GET['section']) && isset($_GET['tab'])) {
    $searchTerm = trim($_GET['search'] ?? '');
    $section = $_GET['section'] === 'deploy' ? 'deploy' : 'borrow';
    $tab = $_GET['tab'] === 'archive' ? 'archive' : 'active';
    $page = isset($_GET['search_page']) ? (int)$_GET['search_page'] : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;
    $output = '';

    $table = $section === 'borrow' ? 'tbl_borrow_assets' : 'tbl_deployment_assets';
    $paginationId = $section === 'borrow' ? ($tab === 'active' ? 'borrowed-pagination' : 'archived-borrow-pagination') : ($tab === 'active' ? 'deployed-pagination' : 'archived-deploy-pagination');
    $statusCondition = $tab === 'active' ? "a_status != 'Archived'" : "a_status = 'Archived'";

    if ($searchTerm === '') {
        $countSql = "SELECT COUNT(*) as total FROM $table WHERE $statusCondition";
        $countResult = $conn->query($countSql);
        $totalRecords = $countResult->fetch_assoc()['total'];
        $totalPages = ceil($totalRecords / $limit);

        $sql = "SELECT a_id, a_name, a_status, a_quantity, a_date 
                FROM $table 
                WHERE $statusCondition 
                LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $offset, $limit);
    } else {
        $countSql = "SELECT COUNT(*) as total FROM $table 
                     WHERE $statusCondition AND (a_name LIKE ? OR a_status LIKE ? OR a_quantity LIKE ? OR a_date LIKE ?)";
        $countStmt = $conn->prepare($countSql);
        $searchWildcard = "%$searchTerm%";
        $countStmt->bind_param("ssss", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRecords = $countResult->fetch_assoc()['total'];
        $countStmt->close();

        $totalPages = ceil($totalRecords / $limit);

        $sql = "SELECT a_id, a_name, a_status, a_quantity, a_date 
                FROM $table 
                WHERE $statusCondition AND (a_name LIKE ? OR a_status LIKE ? OR a_quantity LIKE ? OR a_date LIKE ?)
                LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $offset, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output .= "<tr> 
                          <td>" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "</td> 
                          <td>" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "</td>  
                          <td>" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "</td>
                          <td>" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "</td>  
                          <td>" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                          <td>";
            if ($userType !== 'technician') {
                if ($tab === 'active') {
                    $output .= "<a class='view-btn' onclick=\"show" . ucfirst($section) . "ViewModal('" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                <a class='edit-btn' href='" . ($section === 'borrow' ? 'BorrowE.php' : 'DeployE.php') . "?id=" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "' title='Edit'><i class='fas fa-edit'></i></a>
                                <a class='archive-btn' onclick=\"showArchiveModal('$section', '" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Archive'><i class='fas fa-archive'></i></a>";
                } else {
                    $output .= "<a class='view-btn' onclick=\"show" . ucfirst($section) . "ViewModal('" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                <a class='unarchive-btn' onclick=\"showUnarchiveModal('$section', '" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                <a class='delete-btn' onclick=\"showDeleteModal('$section', '" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>";
                }
            } else {
                $output .= "<a class='view-btn' onclick=\"showRestrictionMessage()\" title='View'><i class='fas fa-eye'></i></a>
                            <a class='edit-btn' onclick=\"showRestrictionMessage()\" title='Edit'><i class='fas fa-edit'></i></a>
                            <a class='archive-btn' onclick=\"showRestrictionMessage()\" title='Archive'><i class='fas fa-archive'></i></a>";
            }
            $output .= "</td></tr>";
        }
    } else {
        $output .= "<tr><td colspan='6'>No assets found.</td></tr>";
    }
    $stmt->close();

    $output .= "<script>updatePagination($page, $totalPages, '$tab', '$searchTerm', '$paginationId');</script>";
    echo $output;
    exit();
}

// Handle archive/unarchive/delete requests (restricted for technicians)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $userType !== 'technician') {
    if (isset($_POST['archive_asset'])) {
        $assetId = $_POST['a_id'];
        $assetType = $_POST['asset_type'];
        $table = $assetType === 'borrow' ? 'tbl_borrow_assets' : 'tbl_deployment_assets';
        $sql = "UPDATE $table SET a_status = 'Archived' WHERE a_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $assetId);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Asset archived successfully!";
        } else {
            $_SESSION['error'] = "Error archiving asset.";
        }
        $stmt->close();
    } elseif (isset($_POST['unarchive_asset'])) {
        $assetId = $_POST['a_id'];
        $assetType = $_POST['asset_type'];
        $table = $assetType === 'borrow' ? 'tbl_borrow_assets' : 'tbl_deployment_assets';
        $sql = "UPDATE $table SET a_status = 'Available' WHERE a_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $assetId);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Asset unarchived successfully!";
        } else {
            $_SESSION['error'] = "Error unarchiving asset.";
        }
        $stmt->close();
    } elseif (isset($_POST['delete_asset'])) {
        $assetId = $_POST['a_id'];
        $assetType = $_POST['category'];
        $table = $assetType === 'borrow' ? 'tbl_borrow_assets' : 'tbl_deployment_assets';
        $sql = "DELETE FROM $table WHERE a_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $assetId);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Asset deleted permanently!";
        } else {
            $_SESSION['error'] = "Error deleting asset.";
        }
        $stmt->close();
    }
    
    $borrowedPage = isset($_GET['borrowed_page']) ? $_GET['borrowed_page'] : 1;
    $deployedPage = isset($_GET['deployed_page']) ? $_GET['deployed_page'] : 1;
    $archivedBorrowPage = isset($_GET['archived_borrow_page']) ? $_GET['archived_borrow_page'] : 1;
    $archivedDeployPage = isset($_GET['archived_deploy_page']) ? $_GET['archived_deploy_page'] : 1;
    header("Location: assetsT.php?borrowed_page=$borrowedPage&deployed_page=$deployedPage&archived_borrow_page=$archivedBorrowPage&archived_deploy_page=$archivedDeployPage");
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $userType === 'technician') {
    $_SESSION['error'] = "Only staff can add, view, edit, or archive assets.";
    $borrowedPage = isset($_GET['borrowed_page']) ? $_GET['borrowed_page'] : 1;
    $deployedPage = isset($_GET['deployed_page']) ? $_GET['deployed_page'] : 1;
    $archivedBorrowPage = isset($_GET['archived_borrow_page']) ? $_GET['archived_borrow_page'] : 1;
    $archivedDeployPage = isset($_GET['archived_deploy_page']) ? $_GET['archived_deploy_page'] : 1;
    header("Location: assetsT.php?borrowed_page=$borrowedPage&deployed_page=$deployedPage&archived_borrow_page=$archivedBorrowPage&archived_deploy_page=$archivedDeployPage");
    exit();
}

// Pagination settings
$limit = 5;

// Borrowed Assets Pagination
$borrowedPage = isset($_GET['borrowed_page']) ? (int)$_GET['borrowed_page'] : 1;
$borrowedOffset = ($borrowedPage - 1) * $limit;

// Deployment Assets Pagination
$deployedPage = isset($_GET['deployed_page']) ? (int)$_GET['deployed_page'] : 1;
$deployedOffset = ($deployedPage - 1) * $limit;

// Archived Assets Pagination
$archivedBorrowPage = isset($_GET['archived_borrow_page']) ? (int)$_GET['archived_borrow_page'] : 1;
$archivedBorrowOffset = ($archivedBorrowPage - 1) * $limit;
$archivedDeployPage = isset($_GET['archived_deploy_page']) ? (int)$_GET['archived_deploy_page'] : 1;
$archivedDeployOffset = ($archivedDeployPage - 1) * $limit;

if ($conn) {
    // Borrowed Assets (Non-Archived)
    $borrowedCountQuery = "SELECT COUNT(*) as total FROM tbl_borrow_assets WHERE a_status != 'Archived'";
    $borrowedCountResult = $conn->query($borrowedCountQuery);
    $totalBorrowed = $borrowedCountResult ? $borrowedCountResult->fetch_assoc()['total'] : 0;
    $totalBorrowedPages = ceil($totalBorrowed / $limit);

    $sqlBorrowed = "SELECT a_id, a_name, a_status, a_quantity, a_date FROM tbl_borrow_assets WHERE a_status != 'Archived' LIMIT ?, ?";
    $stmtBorrowed = $conn->prepare($sqlBorrowed);
    $stmtBorrowed->bind_param("ii", $borrowedOffset, $limit);
    $stmtBorrowed->execute();
    $resultBorrowed = $stmtBorrowed->get_result();
    $stmtBorrowed->close();

    // Deployment Assets (Non-Archived)
    $deployedCountQuery = "SELECT COUNT(*) as total FROM tbl_deployment_assets WHERE a_status != 'Archived'";
    $deployedCountResult = $conn->query($deployedCountQuery);
    $totalDeployed = $deployedCountResult ? $deployedCountResult->fetch_assoc()['total'] : 0;
    $totalDeployedPages = ceil($totalDeployed / $limit);

    $sqlDeployment = "SELECT a_id, a_name, a_status, a_quantity, a_date FROM tbl_deployment_assets WHERE a_status != 'Archived' LIMIT ?, ?";
    $stmtDeployment = $conn->prepare($sqlDeployment);
    $stmtDeployment->bind_param("ii", $deployedOffset, $limit);
    $stmtDeployment->execute();
    $resultDeployment = $stmtDeployment->get_result();
    $stmtDeployment->close();

    // Archived Borrowed Assets
    $archivedBorrowCountQuery = "SELECT COUNT(*) as total FROM tbl_borrow_assets WHERE a_status = 'Archived'";
    $archivedBorrowCountResult = $conn->query($archivedBorrowCountQuery);
    $totalArchivedBorrow = $archivedBorrowCountResult ? $archivedBorrowCountResult->fetch_assoc()['total'] : 0;
    $totalArchivedBorrowPages = ceil($totalArchivedBorrow / $limit);

    $sqlArchivedBorrow = "SELECT a_id, a_name, a_status, a_quantity, a_date FROM tbl_borrow_assets WHERE a_status = 'Archived' LIMIT ?, ?";
    $stmtArchivedBorrow = $conn->prepare($sqlArchivedBorrow);
    $stmtArchivedBorrow->bind_param("ii", $archivedBorrowOffset, $limit);
    $stmtArchivedBorrow->execute();
    $resultArchivedBorrow = $stmtArchivedBorrow->get_result();
    $stmtArchivedBorrow->close();

    // Archived Deployment Assets
    $archivedDeployCountQuery = "SELECT COUNT(*) as total FROM tbl_deployment_assets WHERE a_status = 'Archived'";
    $archivedDeployCountResult = $conn->query($archivedDeployCountQuery);
    $totalArchivedDeploy = $archivedDeployCountResult ? $archivedDeployCountResult->fetch_assoc()['total'] : 0;
    $totalArchivedDeployPages = ceil($totalArchivedDeploy / $limit);

    $sqlArchivedDeploy = "SELECT a_id, a_name, a_status, a_quantity, a_date FROM tbl_deployment_assets WHERE a_status = 'Archived' LIMIT ?, ?";
    $stmtArchivedDeploy = $conn->prepare($sqlArchivedDeploy);
    $stmtArchivedDeploy->bind_param("ii", $archivedDeployOffset, $limit);
    $stmtArchivedDeploy->execute();
    $resultArchivedDeploy = $stmtArchivedDeploy->get_result();
    $stmtArchivedDeploy->close();
} else {
    $_SESSION['error'] = "Database connection failed.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Registration</title>
    <link rel="stylesheet" href="assetsT.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="staffD.php"><img src="https://img.icons8.com/plasticine/100/ticket.png" alt="ticket"/><span>View Tickets</span></a></li>
            <li><a href="assetsT.php" class="active"><img src="https://img.icons8.com/matisse/100/view.png" alt="view"/><span>View Assets</span></a></li>
            
                <li><a href="customersT.php"><img src="https://img.icons8.com/color/48/conference-skin-type-7.png" alt="conference-skin-type-7"/> <span>View Customers</span></a></li>
                <li><a href="registerAssets.php"><img src="https://img.icons8.com/fluency/30/insert.png" alt="insert"/><span>Register Assets</span></a></li>
                <li><a href="addC.php"><img src="https://img.icons8.com/officel/40/add-user-male.png" alt="add-user-male"/><span>Add Customer</span></a></li>
               
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Assets Info</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search assets..." onkeyup="debouncedSearchAssets()">
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
            <div class="borrow">
                <h2>Borrow Assets</h2>
                <div class="header-controls">
                    <div class="tab-buttons">
                        <button class="tab-btn active" onclick="showBorrowTab('active')">Active (<?php echo $totalBorrowed; ?>)</button>
                        <button class="tab-btn" onclick="showBorrowTab('archive')">Archive 
                            <?php if ($totalArchivedBorrow > 0): ?>
                                <span class="tab-badge"><?php echo $totalArchivedBorrow; ?></span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
                <?php if ($userType !== 'technician'): ?>
                    <a href="registerAssets.php" class="add-btn"><i class="fas fa-user-plus"></i> Add Assets</a>
                <?php else: ?>
                    <a href="#" class="add-btn disabled" onclick="showRestrictionMessage()"><i class="fas fa-user-plus"></i> Add Assets</a>
                <?php endif; ?>
                <a href="exportAssets.php" class="export-btn"><i class="fas fa-download"></i> Export</a>
                <div id="borrow-active" class="tab-content">
                    <table id="borrowed-assets-table">
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
                        <tbody id="borrowed-table-body">
                            <?php 
                            if ($resultBorrowed && $resultBorrowed->num_rows > 0) { 
                                while ($row = $resultBorrowed->fetch_assoc()) { 
                                    echo "<tr> 
                                            <td>" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "</td> 
                                            <td>" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "</td>  
                                            <td>" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "</td>  
                                            <td>" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                            <td>";
                                    if ($userType !== 'technician') {
                                        echo "<a class='view-btn' onclick=\"showBorrowViewModal('" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                              <a class='edit-btn' href='BorrowE.php?id=" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "' title='Edit'><i class='fas fa-edit'></i></a>
                                              <a class='archive-btn' onclick=\"showArchiveModal('borrow', '" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Archive'><i class='fas fa-archive'></i></a>";
                                    } else {
                                        echo "<a class='view-btn' onclick=\"showRestrictionMessage()\" title='View'><i class='fas fa-eye'></i></a>
                                              <a class='edit-btn' onclick=\"showRestrictionMessage()\" title='Edit'><i class='fas fa-edit'></i></a>
                                              <a class='archive-btn' onclick=\"showRestrictionMessage()\" title='Archive'><i class='fas fa-archive'></i></a>";
                                    }
                                    echo "</td></tr>"; 
                                } 
                            } else { 
                                echo "<tr><td colspan='6'>No active borrowed assets found.</td></tr>"; 
                            } 
                            ?>
                        </tbody>
                    </table>
                    <div class="pagination" id="borrowed-pagination">
                        <?php if ($borrowedPage > 1): ?>
                            <a href="?borrowed_page=<?php echo $borrowedPage - 1; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        <span class="current-page">Page <?php echo $borrowedPage; ?> of <?php echo $totalBorrowedPages; ?></span>
                        <?php if ($borrowedPage < $totalBorrowedPages): ?>
                            <a href="?borrowed_page=<?php echo $borrowedPage + 1; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="borrow-archive" class="tab-content" style="display: none;">
                    <table id="archived-borrow-assets-table">
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
                        <tbody id="archived-borrow-table-body">
                            <?php 
                            if ($resultArchivedBorrow && $resultArchivedBorrow->num_rows > 0) { 
                                while ($row = $resultArchivedBorrow->fetch_assoc()) { 
                                    echo "<tr> 
                                            <td>" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "</td> 
                                            <td>" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "</td>  
                                            <td>" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "</td>  
                                            <td>" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                            <td>";
                                    if ($userType !== 'technician') {
                                        echo "<a class='view-btn' onclick=\"showBorrowViewModal('" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                              <a class='unarchive-btn' onclick=\"showUnarchiveModal('borrow', '" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                              <a class='delete-btn' onclick=\"showDeleteModal('borrow', '" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>";
                                    } else {
                                        echo "<a class='view-btn' onclick=\"showRestrictionMessage()\" title='View'><i class='fas fa-eye'></i></a>
                                              <a class='unarchive-btn' onclick=\"showRestrictionMessage()\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                              <a class='delete-btn' onclick=\"showRestrictionMessage()\" title='Delete'><i class='fas fa-trash'></i></a>";
                                    }
                                    echo "</td></tr>"; 
                                } 
                            } else { 
                                echo "<tr><td colspan='6'>No archived borrow assets found.</td></tr>"; 
                            } 
                            ?>
                        </tbody>
                    </table>
                    <div class="pagination" id="archived-borrow-pagination">
                        <?php if ($archivedBorrowPage > 1): ?>
                            <a href="?borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage - 1; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        <span class="current-page">Page <?php echo $archivedBorrowPage; ?> of <?php echo $totalArchivedBorrowPages; ?></span>
                        <?php if ($archivedBorrowPage < $totalArchivedBorrowPages): ?>
                            <a href="?borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage + 1; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="deploy">
                <h2>Deployment Assets</h2>
                <div class="header-controls">
                    <div class="tab-buttons">
                        <button class="tab-btn active" onclick="showDeployTab('active')">Active (<?php echo $totalDeployed; ?>)</button>
                        <button class="tab-btn" onclick="showDeployTab('archive')">Archive 
                            <?php if ($totalArchivedDeploy > 0): ?>
                                <span class="tab-badge"><?php echo $totalArchivedDeploy; ?></span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
                <div id="deploy-active" class="tab-content">
                    <table id="deployed-assets-table">
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
                        <tbody id="deployed-table-body">
                            <?php 
                            if ($resultDeployment && $resultDeployment->num_rows > 0) { 
                                while ($row = $resultDeployment->fetch_assoc()) { 
                                    echo "<tr> 
                                            <td>" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "</td> 
                                            <td>" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "</td>  
                                            <td>" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "</td>  
                                            <td>" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                            <td>";
                                    if ($userType !== 'technician') {
                                        echo "<a class='view-btn' onclick=\"showDeployViewModal('" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                              <a class='edit-btn' href='DeployE.php?id=" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "' title='Edit'><i class='fas fa-edit'></i></a>
                                              <a class='archive-btn' onclick=\"showArchiveModal('deploy', '" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Archive'><i class='fas fa-archive'></i></a>";
                                    } else {
                                        echo "<a class='view-btn' onclick=\"showRestrictionMessage()\" title='View'><i class='fas fa-eye'></i></a>
                                              <a class='edit-btn' onclick=\"showRestrictionMessage()\" title='Edit'><i class='fas fa-edit'></i></a>
                                              <a class='archive-btn' onclick=\"showRestrictionMessage()\" title='Archive'><i class='fas fa-archive'></i></a>";
                                    }
                                    echo "</td></tr>"; 
                                } 
                            } else { 
                                echo "<tr><td colspan='6'>No active deployment assets found.</td></tr>"; 
                            } 
                            ?>
                        </tbody>
                    </table>
                    <div class="pagination" id="deployed-pagination">
                        <?php if ($deployedPage > 1): ?>
                            <a href="?borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage - 1; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        <span class="current-page">Page <?php echo $deployedPage; ?> of <?php echo $totalDeployedPages; ?></span>
                        <?php if ($deployedPage < $totalDeployedPages): ?>
                            <a href="?borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage + 1; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="deploy-archive" class="tab-content" style="display: none;">
                    <table id="archived-deploy-assets-table">
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
                        <tbody id="archived-deploy-table-body">
                            <?php 
                            if ($resultArchivedDeploy && $resultArchivedDeploy->num_rows > 0) { 
                                while ($row = $resultArchivedDeploy->fetch_assoc()) { 
                                    echo "<tr> 
                                            <td>" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "</td> 
                                            <td>" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "</td>  
                                            <td>" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "</td>  
                                            <td>" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                            <td>";
                                    if ($userType !== 'technician') {
                                        echo "<a class='view-btn' onclick=\"showDeployViewModal('" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_status'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_quantity'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_date'], ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                              <a class='unarchive-btn' onclick=\"showUnarchiveModal('deploy', '" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                              <a class='delete-btn' onclick=\"showDeleteModal('deploy', '" . htmlspecialchars($row['a_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['a_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>";
                                    } else {
                                        echo "<a class='view-btn' onclick=\"showRestrictionMessage()\" title='View'><i class='fas fa-eye'></i></a>
                                              <a class='unarchive-btn' onclick=\"showRestrictionMessage()\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                              <a class='delete-btn' onclick=\"showRestrictionMessage()\" title='Delete'><i class='fas fa-trash'></i></a>";
                                    }
                                    echo "</td></tr>"; 
                                } 
                            } else { 
                                echo "<tr><td colspan='6'>No archived deployment assets found.</td></tr>"; 
                            } 
                            ?>
                        </tbody>
                    </table>
                    <div class="pagination" id="archived-deploy-pagination">
                        <?php if ($archivedDeployPage > 1): ?>
                            <a href="?borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        <span class="current-page">Page <?php echo $archivedDeployPage; ?> of <?php echo $totalArchivedDeployPages; ?></span>
                        <?php if ($archivedDeployPage < $totalArchivedDeployPages): ?>
                            <a href="?borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="borrowA.php" class="borrow-btn"><i class="fas fa-plus"></i> Borrow</a>
                <a href="return.php" class="return-btn"><i class="fas fa-undo"></i> Return</a>
                <a href="deployA.php" class="deploy-btn"><i class="fas fa-cogs"></i> Deploy</a>
            </div>
        </div>

        <!-- Borrowed Assets View Modal -->
        <div id="borrowViewModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Borrowed Asset Details</h2>
                </div>
                <div id="borrowViewContent"></div>
                <div class="modal-footer">
                    <button class="modal-btn cancel" onclick="closeModal('borrowViewModal')">Close</button>
                </div>
            </div>
        </div>

        <!-- Deployed Assets View Modal -->
        <div id="deployViewModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Deployed Asset Details</h2>
                </div>
                <div id="deployViewContent"></div>
                <div class="modal-footer">
                    <button class="modal-btn cancel" onclick="closeModal('deployViewModal')">Close</button>
                </div>
            </div>
        </div>

        <!-- Archive Confirmation Modal -->
        <div id="archiveModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Archive Asset</h2>
                </div>
                <p>Are you sure you want to archive "<span id="archiveAssetName"></span>"?</p>
                <form method="POST" id="archiveForm">
                    <input type="hidden" name="a_id" id="archiveAssetId">
                    <input type="hidden" name="asset_type" id="archiveAssetType">
                    <input type="hidden" name="archive_asset" value="1">
                    <div class="modal-footer">
                        <button type="button" class="modal-btn cancel" onclick="closeModal('archiveModal')">Cancel</button>
                        <button type="submit" class="modal-btn confirm">Archive</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Unarchive Confirmation Modal -->
        <div id="unarchiveModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Unarchive Asset</h2>
                </div>
                <p>Are you sure you want to unarchive "<span id="unarchiveAssetName"></span>"?</p>
                <form method="POST" id="unarchiveForm">
                    <input type="hidden" name="a_id" id="unarchiveAssetId">
                    <input type="hidden" name="asset_type" id="unarchiveAssetType">
                    <input type="hidden" name="unarchive_asset" value="1">
                    <div class="modal-footer">
                        <button type="button" class="modal-btn cancel" onclick="closeModal('unarchiveModal')">Cancel</button>
                        <button type="submit" class="modal-btn confirm">Unarchive</button>
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
                <p>Are you sure you want to permanently delete "<span id="deleteAssetName"></span>"? This action cannot be undone.</p>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="a_id" id="deleteAssetId">
                    <input type="hidden" name="category" id="deleteAssetType">
                    <input type="hidden" name="delete_asset" value="1">
                    <div class="modal-footer">
                        <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                        <button type="submit" class="modal-btn confirm">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Set default visibility: show active assets for both sections
    showBorrowTab('active');
    showDeployTab('active');

    // Handle alert messages disappearing after 2 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);
    });

    // Initialize search if there's a search term
    const searchInput = document.getElementById('searchInput');
    if (searchInput.value) {
        searchAssets();
    }
});

function showBorrowTab(tab) {
    const activeContent = document.getElementById('borrow-active');
    const archiveContent = document.getElementById('borrow-archive');
    const buttons = document.querySelectorAll('.borrow .tab-buttons .tab-btn');

    if (tab === 'active') {
        activeContent.style.display = 'block';
        archiveContent.style.display = 'none';
    } else {
        activeContent.style.display = 'none';
        archiveContent.style.display = 'block';
    }

    buttons.forEach(button => {
        button.classList.remove('active');
        if (button.getAttribute('onclick').includes(tab)) {
            button.classList.add('active');
        }
    });

    // Trigger search to refresh the table
    searchAssets();
}

function showDeployTab(tab) {
    const activeContent = document.getElementById('deploy-active');
    const archiveContent = document.getElementById('deploy-archive');
    const buttons = document.querySelectorAll('.deploy .tab-buttons .tab-btn');

    if (tab === 'active') {
        activeContent.style.display = 'block';
        archiveContent.style.display = 'none';
    } else {
        activeContent.style.display = 'none';
        archiveContent.style.display = 'block';
    }

    buttons.forEach(button => {
        button.classList.remove('active');
        if (button.getAttribute('onclick').includes(tab)) {
            button.classList.add('active');
        }
    });

    // Trigger search to refresh the table
    searchAssets();
}

function showRestrictionMessage() {
    alert("Only staff can add, view, edit, or archive assets.");
}

function showBorrowViewModal(id, name, status, quantity, date) {
    document.getElementById('borrowViewContent').innerHTML = `
        <div class="asset-details">
            <p><strong>Asset ID:</strong> ${id}</p>
            <p><strong>Asset Name:</strong> ${name}</p>
            <p><strong>Status:</strong> ${status}</p>
            <p><strong>Quantity:</strong> ${quantity}</p>
            <p><strong>Date Registered:</strong> ${date}</p>
        </div>
    `;
    document.getElementById('borrowViewModal').style.display = 'block';
}

function showDeployViewModal(id, name, status, quantity, date) {
    document.getElementById('deployViewContent').innerHTML = `
        <div class="asset-details">
            <p><strong>Asset ID:</strong> ${id}</p>
            <p><strong>Asset Name:</strong> ${name}</p>
            <p><strong>Status:</strong> ${status}</p>
            <p><strong>Quantity:</strong> ${quantity}</p>
            <p><strong>Date Registered:</strong> ${date}</p>
        </div>
    `;
    document.getElementById('deployViewModal').style.display = 'block';
}

function showArchiveModal(type, id, name) {
    document.getElementById('archiveAssetName').textContent = name;
    document.getElementById('archiveAssetId').value = id;
    document.getElementById('archiveAssetType').value = type;
    document.getElementById('archiveModal').style.display = 'block';
}

function showUnarchiveModal(type, id, name) {
    document.getElementById('unarchiveAssetName').textContent = name;
    document.getElementById('unarchiveAssetId').value = id;
    document.getElementById('unarchiveAssetType').value = type;
    document.getElementById('unarchiveModal').style.display = 'block';
}

function showDeleteModal(category, id, name) {
    document.getElementById('deleteAssetName').textContent = name;
    document.getElementById('deleteAssetId').value = id;
    document.getElementById('deleteAssetType').value = category;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Default page numbers for pagination
const defaultBorrowedPage = <?php echo $borrowedPage; ?>;
const defaultDeployedPage = <?php echo $deployedPage; ?>;
const defaultArchivedBorrowPage = <?php echo $archivedBorrowPage; ?>;
const defaultArchivedDeployPage = <?php echo $archivedDeployPage; ?>;
let currentSearchPage = 1;

function searchAssets(page = 1) {
    const searchTerm = document.getElementById('searchInput').value;
    const borrowActive = document.getElementById('borrow-active').style.display !== 'none';
    const borrowArchive = document.getElementById('borrow-archive').style.display !== 'none';
    const deployActive = document.getElementById('deploy-active').style.display !== 'none';
    const deployArchive = document.getElementById('deploy-archive').style.display !== 'none';

    currentSearchPage = page;

    // Handle Borrow section
    if (borrowActive || borrowArchive) {
        const tab = borrowActive ? 'active' : 'archive';
        const tbody = document.getElementById(borrowActive ? 'borrowed-table-body' : 'archived-borrow-table-body');
        const paginationContainer = document.getElementById(borrowActive ? 'borrowed-pagination' : 'archived-borrow-pagination');
        const defaultPageToUse = borrowActive ? defaultBorrowedPage : defaultArchivedBorrowPage;

        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                tbody.innerHTML = xhr.responseText.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
                const scripts = xhr.responseText.match(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi);
                if (scripts) {
                    scripts.forEach(script => {
                        const scriptContent = script.replace(/<\/?script>/g, '');
                        eval(scriptContent);
                    });
                }
            }
        };
        xhr.open('GET', `assetsT.php?action=search&section=borrow&tab=${tab}&search=${encodeURIComponent(searchTerm)}&search_page=${searchTerm ? page : defaultPageToUse}`, true);
        xhr.send();
    }

    // Handle Deploy section
    if (deployActive || deployArchive) {
        const tab = deployActive ? 'active' : 'archive';
        const tbody = document.getElementById(deployActive ? 'deployed-table-body' : 'archived-deploy-table-body');
        const paginationContainer = document.getElementById(deployActive ? 'deployed-pagination' : 'archived-deploy-pagination');
        const defaultPageToUse = deployActive ? defaultDeployedPage : defaultArchivedDeployPage;

        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                tbody.innerHTML = xhr.responseText.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
                const scripts = xhr.responseText.match(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi);
                if (scripts) {
                    scripts.forEach(script => {
                        const scriptContent = script.replace(/<\/?script>/g, '');
                        eval(scriptContent);
                    });
                }
            }
        };
        xhr.open('GET', `assetsT.php?action=search&section=deploy&tab=${tab}&search=${encodeURIComponent(searchTerm)}&search_page=${searchTerm ? page : defaultPageToUse}`, true);
        xhr.send();
    }
}

function updatePagination(currentPage, totalPages, tab, searchTerm, paginationId) {
    const paginationContainer = document.getElementById(paginationId);
    let paginationHtml = '';

    if (currentPage > 1) {
        paginationHtml += `<a href="javascript:searchAssets(${currentPage - 1})" class="pagination-link"><i class="fas fa-chevron-left"></i></a>`;
    } else {
        paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>`;
    }

    paginationHtml += `<span class="current-page">Page ${currentPage} of ${totalPages}</span>`;

    if (currentPage < totalPages) {
        paginationHtml += `<a href="javascript:searchAssets(${currentPage + 1})" class="pagination-link"><i class="fas fa-chevron-right"></i></a>`;
    } else {
        paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>`;
    }

    paginationContainer.innerHTML = paginationHtml;
}

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

const debouncedSearchAssets = debounce(searchAssets, 300);
</script>
</body>
</html>

<?php $conn->close(); ?>