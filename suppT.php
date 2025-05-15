<?php
session_start();
include 'db.php';

// Check user authentication
$username = $_SESSION['username'] ?? '';
$userId = $_SESSION['userId'] ?? 0;
$userType = $_SESSION['user_type'] ?? '';
$isTechnician = $userType === 'technician';
$isCustomer = $userType === 'customer';

if (!$username || (!$isTechnician && !$isCustomer)) {
    $_SESSION['error'] = "Unauthorized access. Please log in.";
    header("Location: index.php");
    exit();
}

// Fetch user details
$firstName = '';
$lastName = '';
if ($isTechnician) {
    $sqlUser = "SELECT u_fname, u_lname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sqlUser);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultUser = $stmt->get_result();
    if ($resultUser->num_rows > 0) {
        $row = $resultUser->fetch_assoc();
        $firstName = $row['u_fname'] ?: $username;
        $lastName = $row['u_lname'] ?: '';
        $userType = $row['u_type'] ?: 'technician';
    } else {
        $_SESSION['error'] = "Technician not found.";
        $stmt->close();
        header("Location: index.php");
        exit();
    }
    $stmt->close();
} elseif ($isCustomer) {
    $sqlUser = "SELECT c_fname, c_lname FROM tbl_customer WHERE c_id = ?";
    $stmt = $conn->prepare($sqlUser);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $resultUser = $stmt->get_result();
    if ($resultUser->num_rows > 0) {
        $row = $resultUser->fetch_assoc();
        $firstName = $row['c_fname'] ?: 'Customer';
        $lastName = $row['c_lname'] ?: '';
    } else {
        $_SESSION['error'] = "Customer not found for ID: $userId.";
        $stmt->close();
        header("Location: customerP.php");
        exit();
    }
    $stmt->close();
}

// Avatar handling
$avatarFolder = 'Uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';
$avatarPath = file_exists($userAvatar) ? $userAvatar . '?' . time() : 'default-avatar.png';
$_SESSION['avatarPath'] = $avatarPath;

// Initialize variables
$ticket = null;
$activeTickets = [];
$archivedTickets = [];
$error = '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
$activePage = isset($_GET['active_page']) ? max(1, (int)$_GET['active_page']) : 1;
$archivedPage = isset($_GET['archived_page']) ? max(1, (int)$_GET['archived_page']) : 1;
$ticketsPerPage = 10;
$filterCid = $isCustomer ? $userId : (isset($_GET['c_id']) && is_numeric($_GET['c_id']) && $_GET['c_id'] > 0 ? (int)$_GET['c_id'] : 0);
$customerName = 'Unknown Customer';

// Validate customer ID
if ($filterCid == 0 && $isTechnician) {
    $error = "Invalid or missing customer ID.";
} else {
    $sql = "SELECT c_fname, c_lname FROM tbl_customer WHERE c_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $filterCid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $customerName = ($row['c_fname'] . ' ' . $row['c_lname']) ?: 'Customer ID ' . $filterCid;
    } else {
        $error = "Customer ID $filterCid does not exist.";
        $filterCid = 0;
    }
    $stmt->close();
}

// Handle create ticket (only for customers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket']) && $isCustomer) {
    $c_id = $userId;
    $c_fname = $firstName;
    $c_lname = $lastName;
    $s_ref = $_POST['ref'] ?? '';
    $s_subject = trim($_POST['subject'] ?? '');
    $s_message = trim($_POST['message'] ?? '');
    $s_status = 'Open';

    if (empty($s_subject) || empty($s_message)) {
        $_SESSION['error'] = "Subject and message are required.";
    } else {
        $sql = "INSERT INTO tbl_supp_tickets (c_id, c_fname, c_lname, s_ref, s_subject, s_message, s_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssss", $c_id, $c_fname, $c_lname, $s_ref, $s_subject, $s_message, $s_status);
        if ($stmt->execute()) {
            error_log("Ticket created with ID: " . $stmt->insert_id . ", Status: $s_status");
            $_SESSION['message'] = "Ticket created successfully!";
        } else {
            error_log("Error creating ticket: " . $stmt->error);
            $_SESSION['error'] = "Error creating ticket.";
        }
        $stmt->close();
    }
    header("Location: suppT.php" . ($filterCid ? "?c_id=$filterCid" : "") . "&tab=$tab&refresh=" . time());
    exit();
}

// Handle edit ticket (only for customers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_ticket']) && $isCustomer) {
    $ticketId = (int)$_POST['t_id'];
    $accountName = trim($_POST['account_name'] ?? '');
    $nameParts = explode(' ', $accountName, 2);
    $c_fname = $nameParts[0] ?? '';
    $c_lname = $nameParts[1] ?? '';
    $s_ref = $_POST['s_ref'] ?? '';
    $s_subject = trim($_POST['s_subject'] ?? '');
    $s_message = trim($_POST['s_message'] ?? '');
    $s_status = $_POST['s_status'] ?? '';

    if (empty($s_subject) || empty($s_message)) {
        $_SESSION['error'] = "Subject and message are required.";
        header("Location: suppT.php?tab=$tab&active_page=$activePage&archived_page=$archivedPage" . ($filterCid ? "&c_id=$filterCid" : ""));
        exit();
    }

    // Fetch current ticket status
    $sqlFetch = "SELECT s_status AS status FROM tbl_supp_tickets WHERE id = ? AND c_id = ?";
    $stmtFetch = $conn->prepare($sqlFetch);
    $stmtFetch->bind_param("ii", $ticketId, $filterCid);
    $stmtFetch->execute();
    $resultFetch = $stmtFetch->get_result();
    if ($resultFetch->num_rows > 0) {
        $currentTicket = $resultFetch->fetch_assoc();
        // Prevent status changes for open or closed tickets
        if ($currentTicket['status'] === 'Open' || $currentTicket['status'] === 'Closed') {
            $s_status = $currentTicket['status'];
        }
    } else {
        $_SESSION['error'] = "Ticket not found or unauthorized.";
        $stmtFetch->close();
        header("Location: suppT.php?tab=$tab&active_page=$activePage&archived_page=$archivedPage" . ($filterCid ? "&c_id=$filterCid" : ""));
        exit();
    }
    $stmtFetch->close();

    $sql = "UPDATE tbl_supp_tickets SET c_fname = ?, c_lname = ?, s_ref = ?, s_subject = ?, s_message = ?, s_status = ? 
            WHERE id = ? AND c_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssii", $c_fname, $c_lname, $s_ref, $s_subject, $s_message, $s_status, $ticketId, $filterCid);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Ticket updated successfully!";
    } else {
        error_log("Error updating ticket ID $ticketId: " . $stmt->error);
        $_SESSION['error'] = "Error updating ticket.";
    }
    $stmt->close();
    header("Location: suppT.php" . ($filterCid ? "?c_id=$filterCid" : "") . "&tab=$tab&refresh=" . time());
    exit();
}

// Handle archive/unarchive ticket (only for customers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_ticket']) && $isCustomer) {
    $ticketId = (int)$_POST['t_id'];
    $newStatus = $_POST['archive_action'] === 'archive' ? 'Archived' : 'Open';
    $sql = "UPDATE tbl_supp_tickets SET s_status = ? WHERE id = ? AND c_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $newStatus, $ticketId, $filterCid);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Ticket " . ($newStatus === 'Archived' ? 'archived' : 'unarchived') . " successfully!";
    } else {
        error_log("Error updating ticket ID $ticketId status to $newStatus: " . $stmt->error);
        $_SESSION['error'] = "Error updating ticket.";
    }
    $stmt->close();
    $redirectTab = $tab;
    if ($tab === 'archived' && $newStatus === 'Open') {
        $redirectTab = 'active';
    }
    header("Location: suppT.php" . ($filterCid ? "?c_id=$filterCid" : "") . "&tab=$redirectTab&active_page=$activePage&archived_page=$archivedPage&refresh=" . time());
    exit();
}

// Handle delete ticket (only for customers, only archived tickets)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ticket']) && $isCustomer) {
    $ticketId = (int)$_POST['t_id'];
    $sql = "DELETE FROM tbl_supp_tickets WHERE id ANIMATIONS ? AND c_id = ? AND s_status = 'Archived'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $ticketId, $filterCid);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['message'] = "Ticket deleted successfully!";
    } else {
        error_log("Error deleting ticket ID $ticketId: " . $stmt->error);
        $_SESSION['error'] = "Error deleting ticket: Invalid ticket or not archived.";
    }
    $stmt->close();
    header("Location: suppT.php" . ($filterCid ? "?c_id=$filterCid" : "") . "&tab=archived&archived_page=$archivedPage&refresh=" . time());
    exit();
}

// Handle close ticket (only for technicians)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_ticket']) && $isTechnician) {
    $ticketId = (int)$_POST['t_id'];
    $inputCid = (int)$_POST['customer_id'];
    $sql = "UPDATE tbl_supp_tickets SET s_status = 'Closed' WHERE id = ? AND c_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $ticketId, $inputCid);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['message'] = "Ticket closed successfully!";
    } else {
        error_log("Error closing ticket ID $ticketId for customer ID $inputCid: " . $stmt->error);
        $_SESSION['error'] = "Error closing ticket: Invalid ticket or customer ID.";
    }
    $stmt->close();
    header("Location: suppT.php" . ($filterCid ? "?c_id=$filterCid" : "") . "&tab=$tab&refresh=" . time());
    exit();
}

// Fetch ticket counts (only if customer ID is valid)
$totalActiveTickets = 0;
$totalArchivedTickets = 0;
if ($filterCid != 0) {
    $sqlCount = "SELECT s_status, COUNT(*) as count FROM tbl_supp_tickets WHERE c_id = ? GROUP BY s_status";
    $stmt = $conn->prepare($sqlCount);
    $stmt->bind_param("i", $filterCid);
    $stmt->execute();
    $resultCount = $stmt->get_result();
    while ($row = $resultCount->fetch_assoc()) {
        if (strtolower($row['s_status']) === 'archived') {
            $totalArchivedTickets = $row['count'];
        } else {
            $totalActiveTickets += $row['count'];
        }
    }
    $stmt->close();
}

// Calculate pagination
$totalActivePages = ceil($totalActiveTickets / $ticketsPerPage) ?: 1;
$totalArchivedPages = ceil($totalArchivedTickets / $ticketsPerPage) ?: 1;
$activePage = max(1, min($totalActivePages, $activePage));
$archivedPage = max(1, min($totalArchivedPages, $archivedPage));
$activeOffset = ($activePage - 1) * $ticketsPerPage;
$archivedOffset = ($archivedPage - 1) * $ticketsPerPage;

// Fetch tickets (only if customer ID is valid)
if ($filterCid != 0) {
    if (isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] > 0) {
        $ticketId = (int)$_GET['id'];
        $sql = "SELECT id, c_id, c_fname, c_lname, s_ref, s_subject, s_message, s_status AS status 
                FROM tbl_supp_tickets 
                WHERE id = ? AND c_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $ticketId, $filterCid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $ticket = $result->fetch_assoc();
        } else {
            $error = "No ticket found with ID $ticketId for customer ID $filterCid.";
        }
        $stmt->close();
    } else {
        // Active tickets (Open and Closed)
        $sql = "SELECT id, c_id, c_fname, c_lname, s_ref, s_subject, s_message, s_status AS status 
                FROM tbl_supp_tickets 
                WHERE s_status IN ('Open', 'Closed') AND c_id = ? 
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed for active tickets: " . $conn->error);
            $_SESSION['error'] = "Error preparing query for active tickets.";
        } else {
            $stmt->bind_param("iii", $filterCid, $ticketsPerPage, $activeOffset);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $activeTickets[] = $row;
                }
            } else {
                error_log("Execute failed for active tickets: " . $stmt->error);
                $_SESSION['error'] = "Error executing query for active tickets.";
            }
            $stmt->close();
        }

        // Archived tickets
        $sql = "SELECT id, c_id, c_fname, c_lname, s_ref, s_subject, s_message, s_status AS status 
                FROM tbl_supp_tickets 
                WHERE s_status = 'Archived' AND c_id = ? 
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed for archived tickets: " . $conn->error);
            $_SESSION['error'] = "Error preparing query for archived tickets.";
        } else {
            $stmt->bind_param("iii", $filterCid, $ticketsPerPage, $archivedOffset);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $archivedTickets[] = $row;
                }
            } else {
                error_log("Execute failed for archived tickets: " . $stmt->error);
                $_SESSION['error'] = "Error executing query for archived tickets.";
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Ticket Details - Customer ID <?php echo htmlspecialchars($filterCid); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="suppT.css">
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2><img src="image/logo.png" alt="Tix Net Icon" class="sidebar-icon">TixNet Pro</h2>
        <ul>
            <?php if ($isTechnician): ?>
                <li><a href="technicianD.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="staffD.php"><i class="fas fa-users"></i> <span>Regular Tickets</span></a></li>
                <li><a href="technicianD.php"><i class="fas fa-file-archive"></i> <span>Support Tickets</span></a></li>
                <li><a href="assetsT.php"><i class="fas fa-box"></i> <span>View Assets</span></a></li>
                <li><a href="techBorrowed.php"><i class="fas fa-box-open"></i> <span>Borrowed Records</span></a></li>
            <?php else: ?>
                <li><a href="portal.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="suppT.php" class="active"><i class="fas fa-file-archive"></i> <span>Support Tickets</span></a></li>
            <?php endif; ?>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </footer>
    </div>
    <div class="container">
        <div class="upper glass-container">
            <h1>Support Tickets</h1>
            <div class="user-profile">
                <div class="user-icon">
                    <?php
                    if (!empty($avatarPath) && file_exists(str_replace('?' . time(), '', $avatarPath))) {
                        echo "<img src='" . htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') . "' alt='User Avatar'>";
                    } else {
                        echo "<i class='fas fa-user-circle'></i>";
                    }
                    ?>
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
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($filterCid != 0): ?>
            <div class="tab-buttons">
                <button class="tab-btn <?php echo $tab === 'active' ? 'active' : ''; ?>" onclick="showTab('active')">Active (<?php echo $totalActiveTickets; ?>)</button>
                <button class="tab-btn <?php echo $tab === 'archived' ? 'active' : ''; ?>" onclick="showTab('archived')">
                    Archived <?php if ($totalArchivedTickets > 0): ?><span class="tab-badge"><?php echo $totalArchivedTickets; ?></span><?php endif; ?>
                </button>
                <button type="button" class="create-ticket-btn" onclick="<?php echo $isTechnician ? 'showRestrictedMessage()' : 'openModal()'; ?>">Create Ticket</button>
            </div>

            <!-- Active Tickets Table -->
            <div class="table-box <?php echo $tab === 'active' ? 'active' : ''; ?>" id="activeTable">
                <?php if (isset($_GET['id']) && $ticket): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket No</th>
                                <th>Customer ID</th>
                                <th>Customer Name</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket['s_ref'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['c_id']); ?></td>
                                <td><?php echo htmlspecialchars(($ticket['c_fname'] . ' ' . $ticket['c_lname']) ?: 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['s_subject'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['s_message'] ?: '-'); ?></td>
                                <td class="status-<?php echo isset($ticket['status']) ? strtolower($ticket['status']) : 'unknown'; ?> status-clickable" 
                                    onclick="<?php echo $isCustomer ? 'showStatusRestrictedMessage()' : "openCloseModal('{$ticket['id']}', '" . htmlspecialchars(($ticket['c_fname'] . ' ' . $ticket['c_lname']) ?: 'Unknown', ENT_QUOTES, 'UTF-8') . "')"; ?>">
                                    <?php echo htmlspecialchars($ticket['status'] ?: 'Unknown'); ?>
                                </td>
                                <td class="action-buttons">
                                    <a class="view-btn" onclick="<?php echo $isTechnician ? 'showRestrictedMessage()' : 'openViewModal(' . htmlspecialchars(json_encode($ticket), ENT_QUOTES, 'UTF-8') . ')'; ?>" title="View"><i class="fas fa-eye"></i></a>
                                    <a class="edit-btn" onclick="<?php echo $isTechnician ? 'showRestrictedMessage()' : 'openEditModal(' . htmlspecialchars(json_encode($ticket), ENT_QUOTES, 'UTF-8') . ')'; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a class="<?php echo $ticket['status'] === 'Archived' ? 'unarchive-btn' : 'archive-btn'; ?>" 
                                       onclick="<?php echo $isTechnician ? 'showRestrictedMessage()' : 'open' . ($ticket['status'] === 'Archived' ? 'Unarchive' : 'Archive') . 'Modal(\'' . $ticket['id'] . '\', \'' . htmlspecialchars(($ticket['c_fname'] . ' ' . $ticket['c_lname']) ?: 'Unknown', ENT_QUOTES, 'UTF-8') . '\')'; ?>" 
                                       title="<?php echo $ticket['status'] === 'Archived' ? 'Unarchive' : 'Archive'; ?>">
                                        <i class="fas fa-<?php echo $ticket['status'] === 'Archived' ? 'box-open' : 'archive'; ?>"></i>
                                    </a>
                                    <?php if ($isCustomer && $ticket['status'] === 'Archived'): ?>
                                        <a class="delete-btn" onclick="openDeleteModal('<?php echo $ticket['id']; ?>', '<?php echo htmlspecialchars(($ticket['c_fname'] . ' ' . $ticket['c_lname']) ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?>')" title="Delete"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php elseif ($tab === 'active' && $activeTickets): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket No</th>
                                <th>Customer ID</th>
                                <th>Customer Name</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeTickets as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['s_ref'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['c_id'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars(($row['c_fname'] . ' ' . $row['c_lname']) ?: 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($row['s_subject'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['s_message'] ?: '-'); ?></td>
                                    <td class="status-<?php echo strtolower($row['status'] ?: 'unknown'); ?> status-clickable" 
                                        onclick="<?php echo $isCustomer ? 'showStatusRestrictedMessage()' : "openCloseModal('{$row['id']}', '" . htmlspecialchars(($row['c_fname'] . ' ' . $row['c_lname']) ?: 'Unknown', ENT_QUOTES, 'UTF-8') . "')"; ?>">
                                        <?php echo htmlspecialchars($row['status'] ?: 'Unknown'); ?>
                                    </td>
                                    <td class="action-buttons">
                                        <a class="view-btn" onclick="<?php echo $isTechnician ? 'showRestrictedMessage()' : 'openViewModal(' . htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') . ')'; ?>" title="View"><i class="fas fa-eye"></i></a>
                                        <a class="edit-btn" onclick="<?php echo $isTechnician ? 'showRestrictedMessage()' : 'openEditModal(' . htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') . ')'; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a class="<?php echo $row['status'] === 'Archived' ? 'unarchive-btn' : 'archive-btn'; ?>" 
                                           onclick="<?php echo $isTechnician ? 'showRestrictedMessage()' : 'open' . ($row['status'] === 'Archived' ? 'Unarchive' : 'Archive') . 'Modal(\'' . $row['id'] . '\', \'' . htmlspecialchars(($row['c_fname'] . ' ' . $row['c_lname']) ?: 'Unknown', ENT_QUOTES, 'UTF-8') . '\')'; ?>" 
                                           title="<?php echo $row['status'] === 'Archived' ? 'Unarchive' : 'Archive'; ?>">
                                            <i class="fas fa-<?php echo $row['status'] === 'Archived' ? 'box-open' : 'archive'; ?>"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php if ($activePage > 1): ?>
                            <a href="?active_page=<?php echo $activePage - 1; ?>&archived_page=<?php echo $archivedPage; ?>&tab=active<?php echo $filterCid ? '&c_id=' . $filterCid : ''; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        <span class="current-page">Page <?php echo $activePage; ?> of <?php echo $totalActivePages; ?></span>
                        <?php if ($activePage < $totalActivePages): ?>
                            <a href="?active_page=<?php echo $activePage + 1; ?>&archived_page=<?php echo $archivedPage; ?>&tab=active<?php echo $filterCid ? '&c_id=' . $filterCid : ''; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php elseif ($tab === 'active'): ?>
                    <div class="empty-state">No active tickets found.</div>
                <?php endif; ?>
            </div>

            <!-- Archived Tickets Table -->
            <div class="table-box <?php echo $tab === 'archived' ? 'active' : ''; ?>" id="archivedTable">
                <?php if ($tab === 'archived' && $archivedTickets): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket No</th>
                                <th>Customer ID</th>
                                <th>Customer Name</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archivedTickets as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['s_ref'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['c_id'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars(($row['c_fname'] . ' ' . $row['c_lname']) ?: 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($row['s_subject'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['s_message'] ?: '-'); ?></td>
                                    <td class="status-<?php echo strtolower($row['status'] ?: 'unknown'); ?> status-clickable" 
                                        onclick="<?php echo $isCustomer ? 'showStatusRestrictedMessage()' : "openCloseModal('{$row['id']}', '" . htmlspecialchars(($row['c_fname'] . ' ' . $row['c_lname']) ?: 'Unknown', ENT_QUOTES, 'UTF-8') . "')"; ?>">
                                        <?php echo htmlspecialchars($row['status'] ?: 'Unknown'); ?>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if ($isCustomer): ?>
                                            <a class="view-btn" onclick="openViewModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)" title="View"><i class="fas fa-eye"></i></a>
                                            <a class="unarchive-btn" onclick="openUnarchiveModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars(($row['c_fname'] . ' ' . $row['c_lname']) ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?>')" title="Unarchive"><i class="fas fa-box-open"></i></a>
                                            <a class="delete-btn" onclick="openDeleteModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars(($row['c_fname'] . ' ' . $row['c_lname']) ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?>')" title="Delete"><i class="fas fa-trash"></i></a>
                                        <?php else: ?>
                                            <a class="view-btn" onclick="showRestrictedMessage()" title="View"><i class="fas fa-eye"></i></a>
                                            <a class="unarchive-btn" onclick="showRestrictedMessage()" title="Unarchive"><i class="fas fa-box-open"></i></a>
                                            <a class="delete-btn" onclick="showRestrictedMessage()" title="Delete"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php if ($archivedPage > 1): ?>
                            <a href="?active_page=<?php echo $activePage; ?>&archived_page=<?php echo $archivedPage - 1; ?>&tab=archived<?php echo $filterCid ? '&c_id=' . $filterCid : ''; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        <span class="current-page">Page <?php echo $archivedPage; ?> of <?php echo $totalArchivedPages; ?></span>
                        <?php if ($archivedPage < $totalArchivedPages): ?>
                            <a href="?active_page=<?php echo $activePage; ?>&archived_page=<?php echo $archivedPage + 1; ?>&tab=archived<?php echo $filterCid ? '&c_id=' . $filterCid : ''; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php elseif ($tab === 'archived'): ?>
                    <div class="empty-state">No archived tickets found.</div>
                <?php endif; ?>
            </div>

            <!-- Modal Background -->
            <div id="modalBackground"></div>

            <!-- Add Ticket Modal -->
            <div id="addTicketModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Add New Ticket</h2>
                    </div>
                    <form method="POST" id="addTicketForm" class="modal-form">
                        <input type="hidden" name="create_ticket" value="1">
                        <input type="hidden" name="c_id" value="<?php echo htmlspecialchars($userId); ?>">
                        <label for="ticket_ref">Reference No</label>
                        <input type="text" name="ref" id="ticket_ref" readonly>
                        <span class="error" id="ticket_ref_error"></span>
                        <label for="account_name">Customer Name</label>
                        <input type="text" name="account_name" id="account_name" value="<?php echo htmlspecialchars($firstName . ' ' . $lastName, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        <span class="error" id="account_name_error"></span>
                        <label for="ticket_subject">Subject</label>
                        <input type="text" name="subject" id="ticket_subject" required>
                        <span class="error" id="ticket_subject_error"></span>
                        <label for="ticket_details">Message</label>
                        <textarea name="message" id="ticket_details" required></textarea>
                        <span class="error" id="ticket_details_error"></span>
                        <input type="hidden" name="s_status" value="Open">
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('addTicketModal')">Cancel</button>
                            <button type="submit" class="modal-btn confirm">Report Ticket</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Ticket Modal -->
            <div id="editTicketModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Edit Ticket</h2>
                    </div>
                    <form method="POST" id="editTicketForm" class="modal-form">
                        <input type="hidden" name="edit_ticket" value="1">
                        <input type="hidden" name="t_id" id="edit_t_id">
                        <label for="edit_ticket_ref">Reference No</label>
                        <input type="text" name="s_ref" id="edit_ticket_ref" readonly>
                        <span class="error" id="edit_ticket_ref_error"></span>
                        <label for="edit_account_name">Customer Name</label>
                        <input type="text" name="account_name" id="edit_account_name" required>
                        <span class="error" id="edit_account_name_error"></span>
                        <label for="edit_ticket_subject">Subject</label>
                        <input type="text" name="s_subject" id="edit_ticket_subject" required>
                        <span class="error" id="edit_ticket_subject_error"></span>
                        <label for="edit_ticket_details">Message</label>
                        <textarea name="s_message" id="edit_ticket_details" required></textarea>
                        <span class="error" id="edit_ticket_details_error"></span>
                        <label for="edit_ticket_status">Ticket Status</label>
                        <input type="text" name="s_status" id="edit_ticket_status" readonly>
                        <span class="error" id="edit_ticket_status_error"></span>
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('editTicketModal')">Cancel</button>
                            <button type="submit" class="modal-btn confirm">Update Ticket</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Archive Ticket Modal -->
            <div id="archiveModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span onclick="closeModal('archiveModal')" class="close">×</span>
                    <div class="modal-header">
                        <h2>Archive Ticket</h2>
                    </div>
                    <p>Are you sure you want to archive ticket <span id="archiveTicketIdDisplay"></span> for <span id="archiveTicketName"></span>?</p>
                    <form class="modal-form" id="archiveForm" method="POST">
                        <input type="hidden" name="t_id" id="archiveTicketId">
                        <input type="hidden" name="archive_ticket" value="1">
                        <input type="hidden" name="archive_action" value="archive">
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('archiveModal')">Cancel</button>
                            <button type="submit" class="modal-btn confirm">Archive Ticket</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Unarchive Ticket Modal -->
            <div id="unarchiveModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span onclick="closeModal('unarchiveModal')" class="close">×</span>
                    <div class="modal-header">
                        <h2>Unarchive Ticket</h2>
                    </div>
                    <p>Are you sure you want to unarchive ticket <span id="unarchiveTicketIdDisplay"></span> for <span id="unarchiveTicketName"></span>?</p>
                    <form class="modal-form" id="unarchiveForm" method="POST">
                        <input type="hidden" name="t_id" id="unarchiveTicketId">
                        <input type="hidden" name="archive_ticket" value="1">
                        <input type="hidden" name="archive_action" value="unarchive">
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('unarchiveModal')">Cancel</button>
                            <button type="submit" class="modal-btn confirm">Unarchive Ticket</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Ticket Modal -->
            <div id="deleteModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span onclick="closeModal('deleteModal')" class="close">×</span>
                    <div class="modal-header">
                        <h2>Delete Ticket</h2>
                    </div>
                    <p>Are you sure you want to delete ticket <span id="deleteTicketIdDisplay"></span> for <span id="deleteTicketName"></span>?</p>
                    <form class="modal-form" id="deleteForm" method="POST">
                        <input type="hidden" name="t_id" id="deleteTicketId">
                        <input type="hidden" name="delete_ticket" value="1">
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                            <button type="submit" class="modal-btn delete">Delete Ticket</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Close Ticket Modal -->
            <div id="closeTicketModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span onclick="closeModal('closeTicketModal')" class="close">×</span>
                    <div class="modal-header">
                        <h2>Close Ticket</h2>
                    </div>
                    <p>Confirm closing ticket ID <span id="closeTicketIdDisplay"></span> for <span id="closeTicketName"></span>?</p>
                    <form class="modal-form" id="closeTicketForm" method="POST">
                        <input type="hidden" name="t_id" id="closeTicketId">
                        <input type="hidden" name="close_ticket" value="1">
                        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($filterCid); ?>">
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('closeTicketModal')">Cancel</button>
                            <button type="submit" class="modal-btn confirm">Close Ticket</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- View Ticket Modal -->
            <div id="viewTicketModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>View Ticket</h2>
                        <span onclick="closeModal('viewTicketModal')" class="close">×</span>
                    </div>
                    <div class="modal-form">
                        <div class="field-row">
                            <label>Ticket No:</label>
                            <p class="view-field" id="viewTicketRef"></p>
                        </div>
                        <div class="field-row">
                            <label>Customer ID:</label>
                            <p class="view-field" id="viewCustomerId"></p>
                        </div>
                        <div class="field-row">
                            <label>Customer Name:</label>
                            <p class="view-field" id="viewCustomerName"></p>
                        </div>
                        <div class="field-row">
                            <label>Subject:</label>
                            <p class="view-field" id="viewSubject"></p>
                        </div>
                        <div class="field-row">
                            <label>Message:</label>
                            <p class="view-field" id="viewMessage" style="white-space: pre-wrap;"></p>
                        </div>
                        <div class="field-row">
                            <label>Status:</label>
                            <p class="view-field" id="viewStatus"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="modal-btn cancel" onclick="closeModal('viewTicketModal')">Close</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'active';
    showTab(tab, false);

    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);
    });
});

function showTab(tab, forceRefresh = true) {
    const activeTable = document.getElementById('activeTable');
    const archivedTable = document.getElementById('archivedTable');
    const allTabButtons = document.querySelectorAll('.tab-btn');

    if (tab === 'active') {
        activeTable.classList.add('active');
        archivedTable.classList.remove('active');
    } else {
        activeTable.classList.remove('active');
        archivedTable.classList.add('active');
    }

    allTabButtons.forEach(button => {
        const buttonTab = button.getAttribute('onclick').match(/'([^']+)'/)[1];
        button.classList.toggle('active', buttonTab === tab);
    });

    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('tab') || 'active';
    const currentCid = urlParams.get('c_id') || '<?php echo $filterCid; ?>';
    const currentActivePage = urlParams.get('active_page') || '<?php echo $activePage; ?>';
    const currentArchivedPage = urlParams.get('archived_page') || '<?php echo $archivedPage; ?>';

    if (forceRefresh && (tab !== currentTab || currentCid !== '<?php echo $filterCid; ?>' || 
        currentActivePage !== '<?php echo $activePage; ?>' || currentArchivedPage !== '<?php echo $archivedPage; ?>')) {
        const newUrl = `suppT.php?tab=${tab}&c_id=<?php echo $filterCid; ?>&active_page=<?php echo $activePage; ?>&archived_page=<?php echo $archivedPage; ?>`;
        window.location.href = newUrl;
    }
}

function openModal() {
    const date = new Date();
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const uniqueNumber = Math.floor(100000 + Math.random() * 900000);
    const ref = `ref#-${day}-${month}-${year}-${uniqueNumber}`;

    document.getElementById('ticket_ref').value = ref;
    document.getElementById('ticket_subject').value = '';
    document.getElementById('ticket_details').value = '';
    document.getElementById('addTicketModal').style.display = 'block';
    document.getElementById('modalBackground').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.getElementById('modalBackground').style.display = 'none';
}

function openEditModal(ticket) {
    document.getElementById('edit_t_id').value = ticket.id;
    document.getElementById('edit_account_name').value = `${ticket.c_fname} ${ticket.c_lname}`.trim() || 'Unknown';
    document.getElementById('edit_ticket_ref').value = ticket.s_ref || '';
    document.getElementById('edit_ticket_subject').value = ticket.s_subject || '';
    document.getElementById('edit_ticket_details').value = ticket.s_message || '';
    document.getElementById('edit_ticket_status').value = ticket.status || 'Open';
    document.getElementById('editTicketModal').style.display = 'block';
    document.getElementById('modalBackground').style.display = 'block';
}

function openArchiveModal(id, name) {
    document.getElementById('archiveTicketId').value = id;
    document.getElementById('archiveTicketIdDisplay').textContent = id;
    document.getElementById('archiveTicketName').textContent = name;
    document.getElementById('archiveModal').style.display = 'block';
    document.getElementById('modalBackground').style.display = 'block';
}

function openUnarchiveModal(id, name) {
    document.getElementById('unarchiveTicketId').value = id;
    document.getElementById('unarchiveTicketIdDisplay').textContent = id;
    document.getElementById('unarchiveTicketName').textContent = name;
    document.getElementById('unarchiveModal').style.display = 'block';
    document.getElementById('modalBackground').style.display = 'block';
}

function openDeleteModal(id, name) {
    document.getElementById('deleteTicketId').value = id;
    document.getElementById('deleteTicketIdDisplay').textContent = id;
    document.getElementById('deleteTicketName').textContent = name;
    document.getElementById('deleteModal').style.display = 'block';
    document.getElementById('modalBackground').style.display = 'block';
}

function openCloseModal(id, name) {
    document.getElementById('closeTicketId').value = id;
    document.getElementById('closeTicketIdDisplay').textContent = id;
    document.getElementById('closeTicketName').textContent = name;
    document.getElementById('closeTicketModal').style.display = 'block';
    document.getElementById('modalBackground').style.display = 'block';
}

function openViewModal(ticket) {
    document.getElementById('viewTicketRef').textContent = ticket.s_ref || '-';
    document.getElementById('viewCustomerId').textContent = ticket.c_id || '-';
    document.getElementById('viewCustomerName').textContent = `${ticket.c_fname} ${ticket.c_lname}`.trim() || 'Unknown';
    document.getElementById('viewSubject').textContent = ticket.s_subject || '-';
    document.getElementById('viewMessage').textContent = ticket.s_message || '-';
    document.getElementById('viewStatus').textContent = ticket.status || 'Unknown';
    document.getElementById('viewTicketModal').style.display = 'block';
    document.getElementById('modalBackground').style.display = 'block';
}

function showRestrictedMessage() {
    alert('This action is restricted to customers only.');
}

function showStatusRestrictedMessage() {
    alert('Only technicians can close tickets.');
}
</script>
</body>
</html>