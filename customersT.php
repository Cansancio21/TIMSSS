<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$firstName = '';
$lastName = '';
$userType = '';
$avatarPath = 'default-avatar.png';
$avatarFolder = 'Uploads/avatars/';
$userAvatar = $avatarFolder . $_SESSION['username'] . '.png';

if (file_exists($userAvatar)) {
    $_SESSION['avatarPath'] = $userAvatar . '?' . time();
} else {
    $_SESSION['avatarPath'] = 'default-avatar.png';
}
$avatarPath = $_SESSION['avatarPath'];

// Handle AJAX search request
if (isset($_GET['action']) && $_GET['action'] === 'search' && isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $tab = $_GET['tab'] ?? 'active';
    $page = isset($_GET['search_page']) ? (int)$_GET['search_page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $searchTerm = $conn->real_escape_string($searchTerm);
    $likeSearch = '%' . $searchTerm . '%';

    if ($tab === 'active') {
        $sqlCount = "SELECT COUNT(*) AS total FROM tbl_customer 
                     WHERE (c_status NOT LIKE 'ARCHIVED:%' OR c_status IS NULL) 
                     AND (c_fname LIKE ? OR c_lname LIKE ? OR c_address LIKE ? OR c_contact LIKE ? OR c_email LIKE ? OR c_napname LIKE ? OR c_napport LIKE ? OR c_macaddress LIKE ?)";
        $sql = "SELECT c_id, c_fname, c_lname, c_address, c_contact, c_email, c_date, c_napname, c_napport, c_macaddress, c_status 
                FROM tbl_customer 
                WHERE (c_status NOT LIKE 'ARCHIVED:%' OR c_status IS NULL) 
                AND (c_fname LIKE ? OR c_lname LIKE ? OR c_address LIKE ? OR c_contact LIKE ? OR c_email LIKE ? OR c_napname LIKE ? OR c_napport LIKE ? OR c_macaddress LIKE ?) 
                LIMIT ?, ?";
    } else {
        $sqlCount = "SELECT COUNT(*) AS total FROM tbl_customer 
                     WHERE c_status LIKE 'ARCHIVED:%' 
                     AND (c_fname LIKE ? OR c_lname LIKE ? OR c_address LIKE ? OR c_contact LIKE ? OR c_email LIKE ? OR c_napname LIKE ? OR c_napport LIKE ? OR c_macaddress LIKE ?)";
        $sql = "SELECT c_id, c_fname, c_lname, c_address, c_contact, c_email, c_date, c_napname, c_napport, c_macaddress, c_status 
                FROM tbl_customer 
                WHERE c_status LIKE 'ARCHIVED:%' 
                AND (c_fname LIKE ? OR c_lname LIKE ? OR c_address LIKE ? OR c_contact LIKE ? OR c_email LIKE ? OR c_napname LIKE ? OR c_napport LIKE ? OR c_macaddress LIKE ?) 
                LIMIT ?, ?";
    }

    // Get total count for pagination
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->bind_param("ssssssss", $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch);
    $stmtCount->execute();
    $countResult = $stmtCount->get_result();
    $totalRow = $countResult->fetch_assoc();
    $total = $totalRow['total'];
    $totalPages = ceil($total / $limit);
    $stmtCount->close();

    // Fetch search results
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssii", $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    ob_start();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $displayStatus = $tab === 'archived' ? preg_replace('/^ARCHIVED:/', '', $row['c_status']) : ($row['c_status'] ?? '');
            echo "<tr> 
                    <td>{$row['c_id']}</td> 
                    <td>" . htmlspecialchars($row['c_fname'], ENT_QUOTES, 'UTF-8') . "</td> 
                    <td>" . htmlspecialchars($row['c_lname'], ENT_QUOTES, 'UTF-8') . "</td> 
                    <td>" . htmlspecialchars($row['c_address'], ENT_QUOTES, 'UTF-8') . "</td> 
                    <td>" . htmlspecialchars($row['c_contact'], ENT_QUOTES, 'UTF-8') . "</td> 
                    <td>" . htmlspecialchars($row['c_email'], ENT_QUOTES, 'UTF-8') . "</td> 
                    <td>" . htmlspecialchars($row['c_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                    <td>" . htmlspecialchars($row['c_napname'], ENT_QUOTES, 'UTF-8') . "</td> 
                    <td>" . htmlspecialchars($row['c_napport'], ENT_QUOTES, 'UTF-8') . "</td> 
                    <td>" . htmlspecialchars($row['c_macaddress'], ENT_QUOTES, 'UTF-8') . "</td> 
                    <td>" . htmlspecialchars($displayStatus, ENT_QUOTES, 'UTF-8') . "</td> 
                    <td class='action-buttons'>";
            if ($tab === 'active') {
                echo "
                    <a class='view-btn' onclick=\"showViewModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_lname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_address'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_contact'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_email'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_date'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_napname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_napport'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_macaddress'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($displayStatus, ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                    <a class='edit-btn' href='editC.php?id=" . htmlspecialchars($row['c_id'], ENT_QUOTES, 'UTF-8') . "' title='Edit'><i class='fas fa-edit'></i></a>
                    <a class='archive-btn' onclick=\"showArchiveModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'] . ' ' . $row['c_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Archive'><i class='fas fa-archive'></i></a>
                    <a class='ticket-btn' onclick=\"showAddTicketModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'] . ' ' . $row['c_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Ticket'><i class='fas fa-ticket-alt'></i></a>";
            } else {
                echo "
                    <a class='view-btn' onclick=\"showViewModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_lname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_address'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_contact'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_email'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_date'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_napname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_napport'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_macaddress'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($displayStatus, ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                    <a class='unarchive-btn' onclick=\"showUnarchiveModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'] . ' ' . $row['c_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                    <a class='delete-btn' onclick=\"showDeleteModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'] . ' ' . $row['c_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>";
            }
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='12' style='text-align: center;'>No customers found.</td></tr>";
    }
    $tableRows = ob_get_clean();

    // Update pagination
    echo "<script>updatePagination($page, $totalPages, '$tab', '" . htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') . "');</script>";
    echo $tableRows;
    $stmt->close();
    $conn->close();
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pageActive = isset($_GET['page_active']) ? (int)$_GET['page_active'] : 1;
    $pageArchived = isset($_GET['page_archived']) ? (int)$_GET['page_archived'] : 1;
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'customers_active';

    if (isset($_POST['add_customer'])) {
        $fname = $_POST['c_fname'];
        $lname = $_POST['c_lname'];
        $address = $_POST['c_address'];
        $contact = $_POST['c_contact'];
        $email = $_POST['c_email'];
        $napname = $_POST['c_napname'];
        $napport = $_POST['c_napport'];
        $macaddress = $_POST['c_macaddress'];
        $status = $_POST['c_status'];

        $sql = "INSERT INTO tbl_customer (c_fname, c_lname, c_address, c_contact, c_email, c_date, c_napname, c_napport, c_macaddress, c_status) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssssssss", $fname, $lname, $address, $contact, $email, $napname, $napport, $macaddress, $status);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer added successfully!";
        } else {
            $_SESSION['error'] = "Error adding customer: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'customers_active';
    } elseif (isset($_POST['edit_customer'])) {
        $id = $_POST['c_id'];
        $fname = $_POST['c_fname'];
        $lname = $_POST['c_lname'];
        $address = $_POST['c_address'];
        $contact = $_POST['c_contact'];
        $email = $_POST['c_email'];
        $napname = $_POST['c_napname'];
        $napport = $_POST['c_napport'];
        $macaddress = $_POST['c_macaddress'];
        $status = $_POST['c_status'];

        $sql = "UPDATE tbl_customer SET c_fname=?, c_lname=?, c_address=?, c_contact=?, c_email=?, c_napname=?, c_napport=?, c_macaddress=?, c_status=? WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssssssssi", $fname, $lname, $address, $contact, $email, $napname, $napport, $macaddress, $status, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating customer: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'customers_active';
    } elseif (isset($_POST['archive_customer'])) {
        $id = $_POST['c_id'];
        $sql = "SELECT c_status FROM tbl_customer WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $current_rem = $row['c_status'] ?? '';
        $stmt->close();

        $new_rem = 'ARCHIVED:' . $current_rem;
        $sql = "UPDATE tbl_customer SET c_status=? WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("si", $new_rem, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer archived successfully!";
        } else {
            $_SESSION['error'] = "Error archiving customer: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'customers_archived';
    } elseif (isset($_POST['unarchive_customer'])) {
        $id = $_POST['c_id'];
        $sql = "SELECT c_status FROM tbl_customer WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $current_rem = $row['c_status'] ?? '';
        $stmt->close();

        $new_rem = preg_replace('/^ARCHIVED:/', '', $current_rem);
        $sql = "UPDATE tbl_customer SET c_status=? WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("si", $new_rem, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer unarchived successfully!";
        } else {
            $_SESSION['error'] = "Error unarchiving customer: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'customers_active';
    } elseif (isset($_POST['delete_customer'])) {
        $id = $_POST['c_id'];
        $sql = "DELETE FROM tbl_customer WHERE c_id=? AND c_status LIKE 'ARCHIVED:%'";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer deleted permanently!";
        } else {
            $_SESSION['error'] = "Error deleting customer: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'customers_archived';
    } elseif (isset($_POST['add_ticket'])) {
        $c_id = $_POST['c_id'];
        $account_name = $_POST['account_name'];
        $issue_type = $_POST['issue_type'];
        $ticket_status = $_POST['ticket_status'];
        $ticket_details = $_POST['ticket_details'];
        $date = $_POST['date'];

        // Basic validation
        $errors = [];
        if (empty($account_name)) {
            $errors[] = "Account name is required.";
        }
        if (empty($issue_type)) {
            $errors[] = "Issue type is required.";
        }
        if (empty($ticket_status)) {
            $errors[] = "Ticket status is required.";
        }
        if (empty($ticket_details)) {
            $errors[] = "Ticket details are required.";
        }
        if (empty($date)) {
            $errors[] = "Date issued is required.";
        }

        if (empty($errors)) {
            $sql = "INSERT INTO tbl_ticket (c_id, t_accountname, t_type, t_status, t_details, t_date) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $_SESSION['error'] = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("isssss", $c_id, $account_name, $issue_type, $ticket_status, $ticket_details, $date);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Ticket added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding ticket: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = implode(" ", $errors);
        }
        $tab = 'customers_active';
    }

    header("Location: customersT.php?tab=$tab&page_active=$pageActive&page_archived=$pageArchived");
    exit();
}

if ($conn) {
    // Fetch user data
    $sqlUser = "SELECT u_fname, u_lname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sqlUser);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $resultUser = $stmt->get_result();
    if ($resultUser->num_rows > 0) {
        $row = $resultUser->fetch_assoc();
        $firstName = $row['u_fname'];
        $lastName = $row['u_lname'];
        $userType = $row['u_type'];
    }
    $stmt->close();

    // Pagination setup
    $limit = 10;
    // Active customers
    $pageActive = isset($_GET['page_active']) ? (int)$_GET['page_active'] : 1;
    $offsetActive = ($pageActive - 1) * $limit;
    $totalActiveQuery = "SELECT COUNT(*) AS total FROM tbl_customer WHERE c_status NOT LIKE 'ARCHIVED:%' OR c_status IS NULL";
    $totalActiveResult = $conn->query($totalActiveQuery);
    $totalActiveRow = $totalActiveResult->fetch_assoc();
    $totalActive = $totalActiveRow['total'];
    $totalActivePages = ceil($totalActive / $limit);

    // Archived customers
    $pageArchived = isset($_GET['page_archived']) ? (int)$_GET['page_archived'] : 1;
    $offsetArchived = ($pageArchived - 1) * $limit;
    $totalArchivedQuery = "SELECT COUNT(*) AS total FROM tbl_customer WHERE c_status LIKE 'ARCHIVED:%'";
    $totalArchivedResult = $conn->query($totalArchivedQuery);
    $totalArchivedRow = $totalArchivedResult->fetch_assoc();
    $totalArchived = $totalArchivedRow['total'];
    $totalArchivedPages = ceil($totalArchived / $limit);

    // Fetch active customers
    $sqlActive = "SELECT c_id, c_fname, c_lname, c_address, c_contact, c_email, c_date, c_napname, c_napport, c_macaddress, c_status 
                  FROM tbl_customer WHERE c_status NOT LIKE 'ARCHIVED:%' OR c_status IS NULL LIMIT ?, ?";
    $stmtActive = $conn->prepare($sqlActive);
    $stmtActive->bind_param("ii", $offsetActive, $limit);
    $stmtActive->execute();
    $resultActive = $stmtActive->get_result();
    $stmtActive->close();

    // Fetch archived customers
    $sqlArchived = "SELECT c_id, c_fname, c_lname, c_address, c_contact, c_email, c_date, c_napname, c_napport, c_macaddress, c_status
                    FROM tbl_customer WHERE c_status LIKE 'ARCHIVED:%' LIMIT ?, ?";
    $stmtArchived = $conn->prepare($sqlArchived);
    $stmtArchived->bind_param("ii", $offsetArchived, $limit);
    $stmtArchived->execute();
    $resultArchived = $stmtArchived->get_result();
    $stmtArchived->close();
} else {
    $_SESSION['error'] = "Database connection failed.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Customers</title>
    <link rel="stylesheet" href="customerT.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2><img src="image/logo.png" alt="Tix Net Icon" class="sidebar-icon">TixNet Pro</h2>
        <ul>
            <li><a href="staffD.php"><img src="https://img.icons8.com/plasticine/100/ticket.png" alt="ticket"/><span>View Tickets</span></a></li>
            <li><a href="assetsT.php"><img src="https://img.icons8.com/matisse/100/view.png" alt="view"/><span>View Assets</span></a></li>
            <li><a href="customersT.php" class="active"><img src="https://img.icons8.com/color/48/conference-skin-type-7.png" alt="conference-skin-type-7"/> <span>View Customers</span></a></li>
            <li><a href="registerAssets.php"><img src="https://img.icons8.com/fluency/30/insert.png" alt="insert"/><span>Register Assets</span></a></li>
            <li><a href="addC.php"><img src="https://img.icons8.com/officel/40/add-user-male.png" alt="add-user-male"/><span>Add Customer</span></a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Customers Info</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search customers..." onkeyup="debouncedSearchCustomers()">
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
            <h2>TIMS Customers</h2>
            <div class="active-customers">
                <div class="tab-buttons">
                    <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'customers_active') || !isset($_GET['tab']) ? 'active' : ''; ?>" onclick="showTab('customers_active')">
                        Active (<?php echo $totalActive; ?>)
                    </button>
                    <button class="tab-btn <?php echo isset($_GET['tab']) && $_GET['tab'] === 'customers_archived' ? 'active' : ''; ?>" onclick="showTab('customers_archived')">
                        Archived
                        <?php if ($totalArchived > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchived; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
                <div class="customer-actions">
                    <form action="addC.php" method="get" style="display: inline;">
                        <button type="submit" class="add-user-btn"><i class="fas fa-user-plus"></i> Add Customer</button>
                    </form>
                    <button class="action-btn export-btn"><i class="fas fa-download"></i> Export</button>
                </div>
                <table id="active-customers-table">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>Nap Name</th>
                            <th>Nap Port</th>
                            <th>Mac Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="active-customers-tbody">
                        <?php
                        if ($resultActive->num_rows > 0) {
                            while ($row = $resultActive->fetch_assoc()) {
                                echo "<tr> 
                                        <td>{$row['c_id']}</td> 
                                        <td>" . htmlspecialchars($row['c_fname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_lname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_address'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_contact'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_email'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_napname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_napport'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_macaddress'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_status'] ?? '', ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td class='action-buttons'>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_lname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_address'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_contact'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_email'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_date'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_napname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_napport'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_macaddress'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_status'] ?? '', ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='edit-btn' href='editC.php?id=" . htmlspecialchars($row['c_id'], ENT_QUOTES, 'UTF-8') . "' title='Edit'><i class='fas fa-edit'></i></a>
                                            <a class='archive-btn' onclick=\"showArchiveModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'] . ' ' . $row['c_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Archive'><i class='fas fa-archive'></i></a>
                                            <a class='ticket-btn' onclick=\"showAddTicketModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'] . ' ' . $row['c_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Ticket'><i class='fas fa-ticket-alt'></i></a>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='12' style='text-align: center;'>No active customers found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div class="pagination" id="active-customers-pagination">
                    <?php if ($pageActive > 1): ?>
                        <a href="?tab=customers_active&page_active=<?php echo $pageActive - 1; ?>&page_archived=<?php echo $pageArchived; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $pageActive; ?> of <?php echo $totalActivePages; ?></span>
                    <?php if ($pageActive < $totalActivePages): ?>
                        <a href="?tab=customers_active&page_active=<?php echo $pageActive + 1; ?>&page_archived=<?php echo $pageArchived; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="archived-customers">
                <div class="tab-buttons">
                    <button class="tab-btn <?php echo isset($_GET['tab']) && $_GET['tab'] === 'customers_active' ? 'active' : ''; ?>" onclick="showTab('customers_active')">
                        Active (<?php echo $totalActive; ?>)
                    </button>
                    <button class="tab-btn <?php echo isset($_GET['tab']) && $_GET['tab'] === 'customers_archived' ? 'active' : ''; ?>" onclick="showTab('customers_archived')">
                        Archived
                        <?php if ($totalArchived > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchived; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
                <table id="archived-customers-table">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>Nap Name</th>
                            <th>NapPort</th>
                            <th>Mac Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="archived-customers-tbody">
                        <?php
                        if ($resultArchived->num_rows > 0) {
                            while ($row = $resultArchived->fetch_assoc()) {
                                $display_rem = preg_replace('/^ARCHIVED:/', '', $row['c_status']);
                                echo "<tr> 
                                        <td>{$row['c_id']}</td> 
                                        <td>" . htmlspecialchars($row['c_fname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_lname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_address'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_contact'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_email'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_napname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_napport'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['c_macaddress'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($display_rem, ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td class='action-buttons'>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_lname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_address'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_contact'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_email'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_date'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_napname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_napport'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['c_macaddress'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($display_rem, ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='unarchive-btn' onclick=\"showUnarchiveModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'] . ' ' . $row['c_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                            <a class='delete-btn' onclick=\"showDeleteModal('{$row['c_id']}', '" . htmlspecialchars($row['c_fname'] . ' ' . $row['c_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='12' style='text-align: center;'>No archived customers found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div class="pagination" id="archived-customers-pagination">
                    <?php if ($pageArchived > 1): ?>
                        <a href="?tab=customers_archived&page_active=<?php echo $pageActive; ?>&page_archived=<?php echo $pageArchived - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $pageArchived; ?> of <?php echo $totalArchivedPages; ?></span>
                    <?php if ($pageArchived < $totalArchivedPages): ?>
                        <a href="?tab=customers_archived&page_active=<?php echo $pageActive; ?>&page_archived=<?php echo $pageArchived + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Customer Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Customer Details</h2>
        </div>
        <div id="viewContent"></div>
        <div class="modal-footer">
            <button class="modal-btn cancel" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- Archive Customer Modal -->
<div id="archiveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Archive Customer</h2>
        </div>
        <p>Are you sure you want to archive <span id="archiveCustomerName"></span>?</p>
        <form method="POST" id="archiveForm">
            <input type="hidden" name="c_id" id="archiveCustomerId">
            <input type="hidden" name="archive_customer" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('archiveModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Archive</button>
            </div>
        </form>
    </div>
</div>

<!-- Unarchive Customer Modal -->
<div id="unarchiveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Unarchive Customer</h2>
        </div>
        <p>Are you sure you want to unarchive <span id="unarchiveCustomerName"></span>?</p>
        <form method="POST" id="unarchiveForm">
            <input type="hidden" name="c_id" id="unarchiveCustomerId">
            <input type="hidden" name="unarchive_customer" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('unarchiveModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Unarchive</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Customer Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Delete Customer</h2>
        </div>
        <p>Are you sure you want to permanently delete <span id="deleteCustomerName"></span>? This action cannot be undone.</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="c_id" id="deleteCustomerId">
            <input type="hidden" name="delete_customer" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Ticket Modal -->
<div id="addTicketModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Ticket</h2>
        </div>
        <form method="POST" id="addTicketForm" class="modal-form">
            <input type="hidden" name="add_ticket" value="1">
            <input type="hidden" name="ajax" value="true">
            <input type="hidden" name="c_id" id="ticketCustomerId">
            <label for="account_name">Account Name</label>
            <input type="text" name="account_name" id="account_name" required readonly>
            <span class="error" id="accountnameErr"></span>
            <label for="issue_type">Issue Type</label>
            <select name="issue_type" id="issue_type" required>
                <option value="">Select Issue Type</option>
                <option value="critical">Critical</option>
                <option value="minor">Minor</option>
            </select>
            <span class="error" id="issuetypeError"></span>
            <label for="ticket_status">Ticket Status</label>
            <select name="ticket_status" id="ticket_status" required>
                <option value="">Select Status</option>
                <option value="Open">Open</option>
                <option value="In Progress">In Progress</option>
            </select>
            <span class="error" id="ticketstatusErr"></span>
            <label for="ticket_details">Ticket Details</label>
            <textarea name="ticket_details" id="ticket_details" required></textarea>
            <span class="error" id="issuedetailsErr"></span>
            <label for="date">Date Issued</label>
            <input type="date" name="date" id="date" required>
            <span class="error" id="dobErr"></span>
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('addTicketModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Add Ticket</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentSearchPage = 1;
let updateInterval = null;

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'customers_active';
    showTab(tab);

    // Handle alert messages disappearing after 2 seconds
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
        searchCustomers();
    }

    // Start auto-update table
    updateInterval = setInterval(updateTable, 30000);
});

// Clear interval when leaving the page
window.addEventListener('beforeunload', () => {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});

function showTab(tab) {
    const activeSection = document.querySelector('.active-customers');
    const archivedSection = document.querySelector('.archived-customers');

    if (tab === 'customers_active') {
        const activeTabButtons = activeSection.querySelectorAll('.tab-btn');
        activeTabButtons.forEach(button => button.classList.remove('active'));
        const activeButton = Array.from(activeTabButtons).find(button => button.onclick.toString().includes(`showTab('customers_active')`));
        if (activeButton) {
            activeButton.classList.add('active');
        }
        activeSection.style.display = 'block';
        archivedSection.style.display = 'none';
    } else if (tab === 'customers_archived') {
        const archivedTabButtons = archivedSection.querySelectorAll('.tab-btn');
        archivedTabButtons.forEach(button => button.classList.remove('active'));
        const archivedButton = Array.from(archivedTabButtons).find(button => button.onclick.toString().includes(`showTab('customers_archived')`));
        if (archivedButton) {
            archivedButton.classList.add('active');
        }
        activeSection.style.display = 'none';
        archivedSection.style.display = 'block';
    }

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', tab);
    history.replaceState(null, '', '?' + urlParams.toString());
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

function searchCustomers(page = 1) {
    const searchTerm = document.getElementById('searchInput').value;
    const activeTab = document.querySelector('.tab-btn.active').textContent.toLowerCase();
    const tab = activeTab.includes('active') ? 'active' : 'archived';
    const tbody = tab === 'active' ? document.getElementById('active-customers-tbody') : document.getElementById('archived-customers-tbody');
    const defaultPageToUse = tab === 'active' ? <?php echo $pageActive; ?> : <?php echo $pageArchived; ?>;

    currentSearchPage = page;

    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            tbody.innerHTML = xhr.responseText.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        }
    };
    xhr.open('GET', `customersT.php?action=search&search=${encodeURIComponent(searchTerm)}&tab=${tab}&search_page=${searchTerm ? page : defaultPageToUse}`, true);
    xhr.send();
}

function updatePagination(currentPage, totalPages, tab, searchTerm) {
    const paginationContainer = tab === 'active' ? document.getElementById('active-customers-pagination') : document.getElementById('archived-customers-pagination');
    let paginationHtml = '';

    if (currentPage > 1) {
        paginationHtml += `<a href="javascript:searchCustomers(${currentPage - 1})" class="pagination-link"><i class="fas fa-chevron-left"></i></a>`;
    } else {
        paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>`;
    }

    paginationHtml += `<span class="current-page">Page ${currentPage} of ${totalPages}</span>`;

    if (currentPage < totalPages) {
        paginationHtml += `<a href="javascript:searchCustomers(${currentPage + 1})" class="pagination-link"><i class="fas fa-chevron-right"></i></a>`;
    } else {
        paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>`;
    }

    paginationContainer.innerHTML = paginationHtml;
}

const debouncedSearchCustomers = debounce(searchCustomers, 300);

function showViewModal(id, fname, lname, area, contact, email, date, onu, caller, address, rem) {
    console.log('showViewModal called with id:', id);
    document.getElementById('viewContent').innerHTML = `
        <div class="customer-details">
            <p><strong>ID:</strong> ${id}</p>
            <p><strong>Name:</strong> ${fname} ${lname}</p>
            <p><strong>Address:</strong> ${area}</p>
            <p><strong>Contact:</strong> ${contact}</p>
            <p><strong>Email:</strong> ${email || 'N/A'}</p>
            <p><strong>Date Applied:</strong> ${date}</p>
            <p><strong>Nap Name:</strong> ${onu || 'N/A'}</p>
            <p><strong>Nap Port:</strong> ${caller || 'N/A'}</p>
            <p><strong>MAC Address:</strong> ${address || 'N/A'}</p>
            <p><strong>Status:</strong> ${rem || 'N/A'}</p>
        </div>
    `;
    document.getElementById('viewModal').style.display = 'block';
}

function showArchiveModal(id, name) {
    console.log('showArchiveModal called with id:', id, 'name:', name);
    document.getElementById('archiveCustomerId').value = id;
    document.getElementById('archiveCustomerName').innerText = name;
    document.getElementById('archiveModal').style.display = 'block';
}

function showUnarchiveModal(id, name) {
    console.log('showUnarchiveModal called with id:', id, 'name:', name);
    document.getElementById('unarchiveCustomerId').value = id;
    document.getElementById('unarchiveCustomerName').innerText = name;
    document.getElementById('unarchiveModal').style.display = 'block';
}

function showDeleteModal(id, name) {
    console.log('showDeleteModal called with id:', id, 'name:', name);
    document.getElementById('deleteCustomerId').value = id;
    document.getElementById('deleteCustomerName').innerText = name;
    document.getElementById('deleteModal').style.display = 'block';
}

function showAddTicketModal(id, name) {
    console.log('showAddTicketModal called with id:', id, 'name:', name);
    // Clear previous errors
    document.querySelectorAll('.modal-form .error').forEach(el => el.innerText = '');
    // Set form values
    document.getElementById('ticketCustomerId').value = id;
    document.getElementById('account_name').value = name;
    document.getElementById('issue_type').value = '';
    document.getElementById('ticket_status').value = '';
    document.getElementById('ticket_details').value = '';
    document.getElementById('date').value = '';
    // Show modal
    document.getElementById('addTicketModal').style.display = 'block';
}

// Handle form submission with AJAX
document.getElementById('addTicketForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('customersT.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        closeModal('addTicketModal');
        // Show alert
        const alertContainer = document.querySelector('.alert-container');
        alertContainer.innerHTML = `
            <div class="alert alert-success">
                ${formData.get('add_ticket') ? 'Ticket added successfully!' : 'Operation completed!'}
            </div>`;
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                alert.classList.add('alert-hidden');
                setTimeout(() => alert.remove(), 500);
            }
        }, 2000);
    })
    .catch(error => {
        console.error('Error:', error);
        const alertContainer = document.querySelector('.alert-container');
        alertContainer.innerHTML = `
            <div class="alert alert-error">Error adding ticket. Please try again.</div>`;
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                alert.classList.add('alert-hidden');
                setTimeout(() => alert.remove(), 500);
            }
        }, 2000);
    });
});

function updateTable() {
    const searchTerm = document.getElementById('searchInput').value;
    const activeTab = document.querySelector('.tab-btn.active').textContent.toLowerCase();
    const tab = activeTab.includes('active') ? 'active' : 'archived';
    const tbody = tab === 'active' ? document.getElementById('active-customers-tbody') : document.getElementById('archived-customers-tbody');
    const defaultPageToUse = tab === 'active' ? <?php echo $pageActive; ?> : <?php echo $pageArchived; ?>;

    if (searchTerm) {
        searchCustomers(currentSearchPage);
    } else {
        fetch(`customersT.php?tab=${tab === 'active' ? 'customers_active' : 'customers_archived'}&page_active=${<?php echo $pageActive; ?>}&page_archived=${<?php echo $pageArchived; ?>}`)
            .then(response => response.text())
            .then(data => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const newTableBody = tab === 'active' ? doc.querySelector('#active-customers-tbody') : doc.querySelector('#archived-customers-tbody');
                const currentTableBody = tab === 'active' ? document.querySelector('#active-customers-tbody') : document.querySelector('#archived-customers-tbody');
                currentTableBody.innerHTML = newTableBody.innerHTML;
            })
            .catch(error => console.error('Error updating table:', error));
    }
}

function closeModal(modalId) {
    console.log('closeModal called for:', modalId);
    document.getElementById(modalId).style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.className === 'modal') {
        console.log('Clicked outside modal, closing');
        event.target.style.display = 'none';
    }
}
</script>
</body>
</html>

<?php $conn->close(); ?>