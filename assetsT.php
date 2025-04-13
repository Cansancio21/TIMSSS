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

// Handle archive/unarchive/delete requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    $borrowTab = isset($_GET['borrow_tab']) ? $_GET['borrow_tab'] : 'borrow_active';
    $deployTab = isset($_GET['deploy_tab']) ? $_GET['deploy_tab'] : 'deploy_active';
    header("Location: assetsT.php?borrow_tab=$borrowTab&deploy_tab=$deployTab&borrowed_page=$borrowedPage&deployed_page=$deployedPage&archived_borrow_page=$archivedBorrowPage&archived_deploy_page=$archivedDeployPage");
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
    <div class="sidebar">
        <h2>Task Management</h2>
        <ul>
            <li><a href="staffD.php"><i class="fas fa-ticket-alt"></i> <span>View Tickets</span></a></li>
            <li><a href="assetsT.php" class="active"><i class="fas fa-box"></i> <span>View Assets</span></a></li>
            <li><a href="customersT.php"><i class="fas fa-users"></i> <span>View Customers</span></a></li>
            <li><a href="createTickets.php"><i class="fas fa-file-invoice"></i> <span>Ticket Registration</span></a></li>
            <li><a href="registerAssets.php"><i class="fas fa-plus-circle"></i> <span>Register Assets</span></a></li>
            <li><a href="addC.php"><i class="fas fa-user-plus"></i> <span>Add Customer</span></a></li>    
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Assets Info</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search assets..." onkeyup="searchAssets()">
                <span class="search-icon"><i class="fas fa-search"></i></span>
            </div>
            <div class="user-profile">
                <div class="user-icon">
                    <?php 
                    if (!empty($avatarPath) && file_exists($avatarPath)) {
                        echo "<img src='" . htmlspecialchars($avatarPath) . "' alt='User Avatar'>";
                    } else {
                        echo "<i class='fas fa-user-circle'></i>";
                    }
                    ?>
                </div>
                <div class="user-details">
                    <span><?php echo htmlspecialchars($firstName); ?></span>
                    <small><?php echo htmlspecialchars(ucfirst($userType)); ?></small>
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
                <div class="tab-buttons">
                    <button class="tab-btn <?php echo (isset($_GET['borrow_tab']) && $_GET['borrow_tab'] === 'borrow_active') || !isset($_GET['borrow_tab']) ? 'active' : ''; ?>" onclick="showTab('borrow_active')">
                        Active (<?php echo $totalBorrowed; ?>)
                    </button>
                    <button class="tab-btn <?php echo isset($_GET['borrow_tab']) && $_GET['borrow_tab'] === 'borrow_archived' ? 'active' : ''; ?>" onclick="showTab('borrow_archived')">
                        Archived
                        <?php if ($totalArchivedBorrow > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchivedBorrow; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
                <a href="registerAssets.php" class="add-btn"><i class="fas fa-user-plus"></i> Add Assets</a>
                <a href="createTickets.php" class="export-btn"><i class="fas fa-download"></i> Export</a>
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
                                            <a class='view-btn' onclick=\"showBorrowViewModal('{$row['a_id']}', '{$row['a_name']}', '{$row['a_status']}', '{$row['a_quantity']}', '{$row['a_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='edit-btn' href='BorrowE.php?id=" . htmlspecialchars($row['a_id']) . "' title='Edit'><i class='fas fa-edit'></i></a>
                                            <a class='archive-btn' onclick=\"showArchiveModal('borrow', '{$row['a_id']}', '{$row['a_name']}')\" title='Archive'><i class='fas fa-archive'></i></a>
                                        </td>
                                      </tr>"; 
                            } 
                        } else { 
                            echo "<tr><td colspan='6'>No borrowed assets found.</td></tr>"; 
                        } 
                        ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php if ($borrowedPage > 1): ?>
                        <a href="?borrow_tab=borrow_active&deploy_tab=<?php echo isset($_GET['deploy_tab']) ? $_GET['deploy_tab'] : 'deploy_active'; ?>&borrowed_page=<?php echo $borrowedPage - 1; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $borrowedPage; ?> of <?php echo $totalBorrowedPages; ?></span>
                    <?php if ($borrowedPage < $totalBorrowedPages): ?>
                        <a href="?borrow_tab=borrow_active&deploy_tab=<?php echo isset($_GET['deploy_tab']) ? $_GET['deploy_tab'] : 'deploy_active'; ?>&borrowed_page=<?php echo $borrowedPage + 1; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="deploy">
                <h2>Deployment Assets</h2>
                <div class="tab-buttons">
                    <button class="tab-btn <?php echo (isset($_GET['deploy_tab']) && $_GET['deploy_tab'] === 'deploy_active') || !isset($_GET['deploy_tab']) ? 'active' : ''; ?>" onclick="showTab('deploy_active')">
                        Active (<?php echo $totalDeployed; ?>)
                    </button>
                    <button class="tab-btn <?php echo isset($_GET['deploy_tab']) && $_GET['deploy_tab'] === 'deploy_archived' ? 'active' : ''; ?>" onclick="showTab('deploy_archived')">
                        Archived
                        <?php if ($totalArchivedDeploy > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchivedDeploy; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
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
                                            <a class='view-btn' onclick=\"showDeployViewModal('{$row['a_id']}', '{$row['a_name']}', '{$row['a_status']}', '{$row['a_quantity']}', '{$row['a_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='edit-btn' href='DeployE.php?id=" . htmlspecialchars($row['a_id']) . "' title='Edit'><i class='fas fa-edit'></i></a>
                                            <a class='archive-btn' onclick=\"showArchiveModal('deploy', '{$row['a_id']}', '{$row['a_name']}')\" title='Archive'><i class='fas fa-archive'></i></a>
                                        </td>
                                      </tr>"; 
                            } 
                        } else { 
                            echo "<tr><td colspan='6'>No deployment assets found.</td></tr>"; 
                        } 
                        ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php if ($deployedPage > 1): ?>
                        <a href="?borrow_tab=<?php echo isset($_GET['borrow_tab']) ? $_GET['borrow_tab'] : 'borrow_active'; ?>&deploy_tab=deploy_active&borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage - 1; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $deployedPage; ?> of <?php echo $totalDeployedPages; ?></span>
                    <?php if ($deployedPage < $totalDeployedPages): ?>
                        <a href="?borrow_tab=<?php echo isset($_GET['borrow_tab']) ? $_GET['borrow_tab'] : 'borrow_active'; ?>&deploy_tab=deploy_active&borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage + 1; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
                <a href="borrowA.php" class="borrow-btn"><i class="fas fa-plus"></i> Borrow</a>
                <a href="return.php" class="return-btn"><i class="fas fa-undo"></i> Return</a>
                <a href="deployA.php" class="deploy-btn"><i class="fas fa-cogs"></i> Deploy</a>
            </div>

            <div class="archive-borrow">
                <h2>Archived Borrow Assets</h2>
                <div class="tab-buttons">
                    <button class="tab-btn <?php echo isset($_GET['borrow_tab']) && $_GET['borrow_tab'] === 'borrow_active' ? 'active' : ''; ?>" onclick="showTab('borrow_active')">
                        Active (<?php echo $totalBorrowed; ?>)
                    </button>
                    <button class="tab-btn <?php echo (isset($_GET['borrow_tab']) && $_GET['borrow_tab'] === 'borrow_archived') || !isset($_GET['borrow_tab']) ? 'active' : ''; ?>" onclick="showTab('borrow_archived')">
                        Archived
                        <?php if ($totalArchivedBorrow > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchivedBorrow; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
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
                    <tbody>
                        <?php 
                        if ($resultArchivedBorrow && $resultArchivedBorrow->num_rows > 0) { 
                            while ($row = $resultArchivedBorrow->fetch_assoc()) { 
                                echo "<tr> 
                                        <td>{$row['a_id']}</td> 
                                        <td>{$row['a_name']}</td>  
                                        <td>{$row['a_status']}</td>
                                        <td>{$row['a_quantity']}</td>  
                                        <td>{$row['a_date']}</td> 
                                        <td>
                                            <a class='view-btn' onclick=\"showBorrowViewModal('{$row['a_id']}', '{$row['a_name']}', '{$row['a_status']}', '{$row['a_quantity']}', '{$row['a_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='unarchive-btn' onclick=\"showUnarchiveModal('borrow', '{$row['a_id']}', '{$row['a_name']}')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                            <a class='delete-btn' onclick=\"showDeleteModal('borrow', '{$row['a_id']}', '{$row['a_name']}')\" title='Delete'><i class='fas fa-trash'></i></a>
                                        </td>
                                      </tr>"; 
                            } 
                        } else { 
                            echo "<tr><td colspan='6'>No archived borrow assets found.</td></tr>"; 
                        } 
                        ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php if ($archivedBorrowPage > 1): ?>
                        <a href="?borrow_tab=borrow_archived&deploy_tab=<?php echo isset($_GET['deploy_tab']) ? $_GET['deploy_tab'] : 'deploy_active'; ?>&borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage - 1; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $archivedBorrowPage; ?> of <?php echo $totalArchivedBorrowPages; ?></span>
                    <?php if ($archivedBorrowPage < $totalArchivedBorrowPages): ?>
                        <a href="?borrow_tab=borrow_archived&deploy_tab=<?php echo isset($_GET['deploy_tab']) ? $_GET['deploy_tab'] : 'deploy_active'; ?>&borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage + 1; ?>&archived_deploy_page=<?php echo $archivedDeployPage; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="archive-deploy">
                <h2>Archived Deployment Assets</h2>
                <div class="tab-buttons">
                    <button class="tab-btn <?php echo isset($_GET['deploy_tab']) && $_GET['deploy_tab'] === 'deploy_active' ? 'active' : ''; ?>" onclick="showTab('deploy_active')">
                        Active (<?php echo $totalDeployed; ?>)
                    </button>
                    <button class="tab-btn <?php echo (isset($_GET['deploy_tab']) && $_GET['deploy_tab'] === 'deploy_archived') || !isset($_GET['deploy_tab']) ? 'active' : ''; ?>" onclick="showTab('deploy_archived')">
                        Archived
                        <?php if ($totalArchivedDeploy > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchivedDeploy; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
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
                    <tbody>
                        <?php 
                        if ($resultArchivedDeploy && $resultArchivedDeploy->num_rows > 0) { 
                            while ($row = $resultArchivedDeploy->fetch_assoc()) { 
                                echo "<tr> 
                                        <td>{$row['a_id']}</td> 
                                        <td>{$row['a_name']}</td>  
                                        <td>{$row['a_status']}</td>
                                        <td>{$row['a_quantity']}</td>  
                                        <td>{$row['a_date']}</td> 
                                        <td>
                                            <a class='view-btn' onclick=\"showDeployViewModal('{$row['a_id']}', '{$row['a_name']}', '{$row['a_status']}', '{$row['a_quantity']}', '{$row['a_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='unarchive-btn' onclick=\"showUnarchiveModal('deploy', '{$row['a_id']}', '{$row['a_name']}')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                            <a class='delete-btn' onclick=\"showDeleteModal('deploy', '{$row['a_id']}', '{$row['a_name']}')\" title='Delete'><i class='fas fa-trash'></i></a>
                                        </td>
                                      </tr>"; 
                            } 
                        } else { 
                            echo "<tr><td colspan='6'>No archived deployment assets found.</td></tr>"; 
                        } 
                        ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php if ($archivedDeployPage > 1): ?>
                        <a href="?borrow_tab=<?php echo isset($_GET['borrow_tab']) ? $_GET['borrow_tab'] : 'borrow_active'; ?>&deploy_tab=deploy_archived&borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $archivedDeployPage; ?> of <?php echo $totalArchivedDeployPages; ?></span>
                    <?php if ($archivedDeployPage < $totalArchivedDeployPages): ?>
                        <a href="?borrow_tab=<?php echo isset($_GET['borrow_tab']) ? $_GET['borrow_tab'] : 'borrow_active'; ?>&deploy_tab=deploy_archived&borrowed_page=<?php echo $borrowedPage; ?>&deployed_page=<?php echo $deployedPage; ?>&archived_borrow_page=<?php echo $archivedBorrowPage; ?>&archived_deploy_page=<?php echo $archivedDeployPage + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const borrowTab = urlParams.get('borrow_tab') || 'borrow_active';
    const deployTab = urlParams.get('deploy_tab') || 'deploy_active';
    showTab(borrowTab);
    showTab(deployTab);

    // Handle alert messages disappearing after 2 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);
    });
});

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

function showTab(tab) {
    const borrowSection = document.querySelector('.borrow');
    const deploySection = document.querySelector('.deploy');
    const archiveBorrowSection = document.querySelector('.archive-borrow');
    const archiveDeploySection = document.querySelector('.archive-deploy');

    // Handle Borrow Tabs
    if (tab === 'borrow_active') {
        const borrowTabButtons = borrowSection.querySelectorAll('.tab-btn');
        borrowTabButtons.forEach(button => button.classList.remove('active'));
        const activeBorrowButton = Array.from(borrowTabButtons).find(button => button.onclick.toString().includes(`showTab('borrow_active')`));
        if (activeBorrowButton) {
            activeBorrowButton.classList.add('active');
        }
        borrowSection.style.display = 'block';
        archiveBorrowSection.style.display = 'none';

        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('borrow_tab', tab);
        history.replaceState(null, '', '?' + urlParams.toString());
    } else if (tab === 'borrow_archived') {
        const archiveBorrowTabButtons = archiveBorrowSection.querySelectorAll('.tab-btn');
        archiveBorrowTabButtons.forEach(button => button.classList.remove('active'));
        const activeArchiveBorrowButton = Array.from(archiveBorrowTabButtons).find(button => button.onclick.toString().includes(`showTab('borrow_archived')`));
        if (activeArchiveBorrowButton) {
            activeArchiveBorrowButton.classList.add('active');
        }
        borrowSection.style.display = 'none';
        archiveBorrowSection.style.display = 'block';

        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('borrow_tab', tab);
        history.replaceState(null, '', '?' + urlParams.toString());
    }

    // Handle Deploy Tabs
    if (tab === 'deploy_active') {
        const deployTabButtons = deploySection.querySelectorAll('.tab-btn');
        deployTabButtons.forEach(button => button.classList.remove('active'));
        const activeDeployButton = Array.from(deployTabButtons).find(button => button.onclick.toString().includes(`showTab('deploy_active')`));
        if (activeDeployButton) {
            activeDeployButton.classList.add('active');
        }
        deploySection.style.display = 'block';
        archiveDeploySection.style.display = 'none';

        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('deploy_tab', tab);
        history.replaceState(null, '', '?' + urlParams.toString());
    } else if (tab === 'deploy_archived') {
        const archiveDeployTabButtons = archiveDeploySection.querySelectorAll('.tab-btn');
        archiveDeployTabButtons.forEach(button => button.classList.remove('active'));
        const activeArchiveDeployButton = Array.from(archiveDeployTabButtons).find(button => button.onclick.toString().includes(`showTab('deploy_archived')`));
        if (activeArchiveDeployButton) {
            activeArchiveDeployButton.classList.add('active');
        }
        deploySection.style.display = 'none';
        archiveDeploySection.style.display = 'block';

        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('deploy_tab', tab);
        history.replaceState(null, '', '?' + urlParams.toString());
    }
}

function searchAssets() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    
    // Search Borrowed Assets
    const borrowedTable = document.getElementById('borrowed-assets-table');
    if (borrowedTable && document.querySelector('.borrow').style.display !== 'none') {
        const borrowedRows = borrowedTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        for (let i = 0; i < borrowedRows.length; i++) {
            const cells = borrowedRows[i].getElementsByTagName('td');
            let match = false;
            for (let j = 0; j < cells.length - 1; j++) {
                if (cells[j].textContent.toLowerCase().includes(input)) {
                    match = true;
                    break;
                }
            }
            borrowedRows[i].style.display = match ? '' : 'none';
        }
    }

    // Search Deployed Assets
    const deployedTable = document.getElementById('deployed-assets-table');
    if (deployedTable && document.querySelector('.deploy').style.display !== 'none') {
        const deployedRows = deployedTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        for (let i = 0; i < deployedRows.length; i++) {
            const cells = deployedRows[i].getElementsByTagName('td');
            let match = false;
            for (let j = 0; j < cells.length - 1; j++) {
                if (cells[j].textContent.toLowerCase().includes(input)) {
                    match = true;
                    break;
                }
            }
            deployedRows[i].style.display = match ? '' : 'none';
        }
    }

    // Search Archived Borrow Assets
    const archivedBorrowTable = document.getElementById('archived-borrow-assets-table');
    if (archivedBorrowTable && document.querySelector('.archive-borrow').style.display !== 'none') {
        const archivedBorrowRows = archivedBorrowTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        for (let i = 0; i < archivedBorrowRows.length; i++) {
            const cells = archivedBorrowRows[i].getElementsByTagName('td');
            let match = false;
            for (let j = 0; j < cells.length - 1; j++) {
                if (cells[j].textContent.toLowerCase().includes(input)) {
                    match = true;
                    break;
                }
            }
            archivedBorrowRows[i].style.display = match ? '' : 'none';
        }
    }

    // Search Archived Deploy Assets
    const archivedDeployTable = document.getElementById('archived-deploy-assets-table');
    if (archivedDeployTable && document.querySelector('.archive-deploy').style.display !== 'none') {
        const archivedDeployRows = archivedDeployTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        for (let i = 0; i < archivedDeployRows.length; i++) {
            const cells = archivedDeployRows[i].getElementsByTagName('td');
            let match = false;
            for (let j = 0; j < cells.length - 1; j++) {
                if (cells[j].textContent.toLowerCase().includes(input)) {
                    match = true;
                    break;
                }
            }
            archivedDeployRows[i].style.display = match ? '' : 'none';
        }
    }
}
</script>
</body>
</html>

<?php $conn->close(); ?>